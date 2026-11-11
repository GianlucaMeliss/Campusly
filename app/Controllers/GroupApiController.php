<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\RateLimiter;
use App\Core\Str;
use App\Models\GroupModel;
use App\Models\HiddenCourseModel;
use App\Models\PersonalEventModel;
use App\Models\UserModel;
use App\Services\ScheduleService;
use InvalidArgumentException;
use Throwable;

final class GroupApiController
{
    private const PRIVACY_LEVELS = ['transparent', 'logistical', 'opaque'];

    public function createGroup(): void
    {
        $userId = Http::requireUserId();
        $data = Http::input();

        $name = isset($data['name']) && is_string($data['name']) ? Str::cut(trim($data['name']), 100) : '';
        $privacy = $data['privacy_level'] ?? 'transparent';

        if ($name === '') {
            Http::json(['error' => 'Nome gruppo mancante'], 400);
        }

        if (!is_string($privacy) || !in_array($privacy, self::PRIVACY_LEVELS, true)) {
            Http::json(['error' => 'Livello di privacy non valido'], 400);
        }

        $model = new GroupModel();
        Http::json($model->createGroup($userId, $name, $privacy));
    }

    public function joinGroup(): void
    {
        $userId = Http::requireUserId();
        $limiter = new RateLimiter();
        $bucketKey = (string)$userId;

        if ($limiter->tooManyAttempts('join:user', $bucketKey, 10, 600)) {
            Http::json(['error' => 'Troppi tentativi. Riprova tra qualche minuto.'], 429);
        }

        $data = Http::input();
        $code = isset($data['invite_code']) && is_string($data['invite_code'])
            ? strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $data['invite_code']))
            : '';
        $privacy = $data['privacy_level'] ?? 'logistical';

        if ($code === '' || strlen($code) > 16) {
            Http::json(['error' => 'Codice mancante o non valido'], 400);
        }

        if (!is_string($privacy) || !in_array($privacy, self::PRIVACY_LEVELS, true)) {
            Http::json(['error' => 'Livello di privacy non valido'], 400);
        }

        $model = new GroupModel();
        $result = $model->joinGroup($userId, $code, $privacy);

        if (($result['status'] ?? '') !== 'success') {
            $limiter->hit('join:user', $bucketKey);
        }

        Http::json($result);
    }

    public function leaveGroup(): void
    {
        $userId = Http::requireUserId();
        $data = Http::input();
        $groupId = (int)($data['group_id'] ?? 0);

        if ($groupId <= 0) {
            Http::json(['error' => 'ID gruppo non valido'], 400);
        }

        $model = new GroupModel();
        $model->leaveGroup($userId, $groupId);
        Http::json(['status' => 'success']);
    }

    public function updateGroupPrivacy(): void
    {
        $userId = Http::requireUserId();
        $data = Http::input();
        $groupId = (int)($data['group_id'] ?? 0);
        $privacy = (string)($data['privacy_level'] ?? '');

        if ($groupId <= 0 || !in_array($privacy, self::PRIVACY_LEVELS, true)) {
            Http::json(['error' => 'Dati non validi'], 400);
        }

        $model = new GroupModel();
        $model->updatePrivacy($userId, $groupId, $privacy);
        Http::json(['status' => 'success']);
    }

    private static function isCourseHidden(string $courseName, array $hiddenCourses): bool
    {
        $normCourse = trim(strtoupper($courseName));
        if ($normCourse === '') {
            return false;
        }
        foreach ($hiddenCourses as $hc) {
            $normHidden = trim(strtoupper((string)$hc));
            if ($normHidden !== '' && $normCourse === $normHidden) {
                return true;
            }
        }
        return false;
    }

    public function getGroupCalendar(string $groupId): void
    {
        $userId = Http::requireUserId();
        $gid = (int)$groupId;

        $groupModel = new GroupModel();
        if (!$groupModel->isMember($userId, $gid)) {
            Http::json(['error' => 'Accesso negato al gruppo'], 403);
        }

        $dataInizio = (string)($_GET['inizio'] ?? date('Y-m-d\T00:00:00.000\Z'));
        $dataFine = (string)($_GET['fine'] ?? date('Y-m-d\T23:59:59.000\Z', strtotime('+7 days')));

        $scheduleService = new ScheduleService();
        try {
            $scheduleService->validateDateRange($dataInizio, $dataFine);
        } catch (InvalidArgumentException $e) {
            Http::json(['error' => $e->getMessage()], 400);
        }

        $members = $groupModel->getGroupMembers($gid);
        $megaEvents = [];
        $userModel = new UserModel();
        $hiddenCourseModel = new HiddenCourseModel();
        $personalEventModel = new PersonalEventModel();

        // Deduplica in-memory per membri che seguono esattamente gli stessi corsi
        $memoizedSchedules = [];

        foreach ($members as $member) {
            $memberId = (int)$member['id'];
            $privacy = (string)$member['privacy_level'];
            $isMe = ($memberId === $userId);

            $hiddenCourses = $hiddenCourseModel->getHiddenCourses($memberId);
            $userCourses = $userModel->getUserCourses($memberId);

            $eventiUniv = [];
            if (!empty($userCourses)) {
                $cacheKey = $scheduleService->cacheKey($dataInizio, $dataFine, $userCourses);
                if (!array_key_exists($cacheKey, $memoizedSchedules)) {
                    try {
                        $res = $scheduleService->getEventsForUser($memberId, $userCourses, $dataInizio, $dataFine);
                        $memoizedSchedules[$cacheKey] = $res['events'];
                    } catch (Throwable) {
                        $memoizedSchedules[$cacheKey] = [];
                    }
                }
                $eventiUniv = $memoizedSchedules[$cacheKey];
            }

            $eventiPers = $personalEventModel->getUserEvents($memberId, $dataInizio, $dataFine);

            foreach ($eventiUniv as $ev) {
                if (($ev['stato'] ?? '') === 'A') {
                    continue;
                }
                if (self::isCourseHidden((string)($ev['nome'] ?? ''), $hiddenCourses)) {
                    continue;
                }

                // Whitelist esplicita dei campi in base al livello di privacy
                if ($isMe) {
                    $ev['member_id'] = $memberId;
                    $ev['is_me'] = true;
                    $megaEvents[] = $ev;
                } elseif ($privacy === 'opaque') {
                    $megaEvents[] = [
                        'idPersonale' => null,
                        'nome'        => 'Occupato',
                        'dataInizio'  => $ev['dataInizio'] ?? '',
                        'dataFine'    => $ev['dataFine'] ?? '',
                        'stato'       => 'C',
                        'isPersonale' => false,
                        'member_id'   => $memberId,
                        'is_me'       => false,
                    ];
                } elseif ($privacy === 'logistical') {
                    $auleSafe = [];
                    if (!empty($ev['risorse']) && is_array($ev['risorse'])) {
                        foreach ($ev['risorse'] as $r) {
                            if (isset($r['aula'])) {
                                $auleSafe[] = [
                                    'aula' => [
                                        'descrizione' => $r['aula']['descrizione'] ?? '',
                                        'edificio'    => [
                                            'descrizione' => $r['aula']['edificio']['descrizione'] ?? '',
                                        ],
                                    ],
                                ];
                            }
                        }
                    }
                    $megaEvents[] = [
                        'idPersonale' => null,
                        'nome'        => 'Occupato (' . $member['first_name'] . ')',
                        'dataInizio'  => $ev['dataInizio'] ?? '',
                        'dataFine'    => $ev['dataFine'] ?? '',
                        'stato'       => 'C',
                        'risorse'     => $auleSafe,
                        'isPersonale' => false,
                        'member_id'   => $memberId,
                        'is_me'       => false,
                    ];
                } else {
                    // transparent
                    $ev['nome'] = ($ev['nome'] ?? 'Lezione') . ' (' . $member['first_name'] . ')';
                    $ev['member_id'] = $memberId;
                    $ev['is_me'] = false;
                    $megaEvents[] = $ev;
                }
            }

            foreach ($eventiPers as $ep) {
                $evFormat = [
                    'idPersonale' => $ep['id'],
                    'dataInizio'  => str_replace(' ', 'T', (string)$ep['start_time']) . 'Z',
                    'dataFine'    => str_replace(' ', 'T', (string)$ep['end_time']) . 'Z',
                    'isPersonale' => true,
                    'member_id'   => $memberId,
                    'is_me'       => $isMe,
                ];

                if ($isMe || $privacy === 'transparent') {
                    $evFormat['nome'] = $isMe ? $ep['title'] : $ep['title'] . ' (' . $member['first_name'] . ')';
                    $evFormat['luogo'] = $ep['location'];
                } else {
                    $evFormat['nome'] = 'Occupato (' . $member['first_name'] . ')';
                    $evFormat['luogo'] = 'Non condiviso';
                }

                $megaEvents[] = $evFormat;
            }
        }

        $groupInfo = [];
        foreach ($members as $m) {
            $groupInfo[$m['id']] = [
                'name'      => $m['first_name'],
                'last_name' => $m['last_name'] ?? '',
                'privacy'   => $m['privacy_level'],
            ];
        }

        Http::json([
            'current_user_id' => $userId,
            'members'         => $groupInfo,
            'events'          => $megaEvents,
        ]);
    }
}
