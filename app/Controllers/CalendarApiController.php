<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Str;
use App\Models\HiddenCourseModel;
use App\Models\PersonalEventModel;
use App\Models\UserModel;
use App\Services\ScheduleService;
use InvalidArgumentException;
use Throwable;

final class CalendarApiController
{
    public function getCalendarEvents(): void
    {
        $userId = Http::requireUserId();

        $dataInizio = (string)($_GET['inizio'] ?? date('Y-m-d\T00:00:00.000\Z'));
        $dataFine = (string)($_GET['fine'] ?? date('Y-m-d\T23:59:59.000\Z', strtotime('+7 days')));

        $userModel = new UserModel();
        $userCourses = $userModel->getUserCourses($userId);

        if (empty($userCourses)) {
            Http::json(['error' => 'Nessun corso configurato'], 400);
        }

        $service = new ScheduleService();
        try {
            $result = $service->getEventsForUser($userId, $userCourses, $dataInizio, $dataFine);
            $headers = ['X-Cache-Status' => $result['cache_status']];
            if ($result['partial_failure']) {
                $headers['X-Partial-Failure'] = '1';
            }
            Http::json($result['events'], 200, $headers);
        } catch (InvalidArgumentException $e) {
            Http::json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            error_log('[getCalendarEvents] ' . $e->getMessage());
            Http::json(['status' => 'error', 'message' => 'Servizio orari temporaneamente non disponibile'], 500);
        }
    }

    public function getPersonalEvents(): void
    {
        $userId = Http::requireUserId();
        $from = isset($_GET['inizio']) ? (string)$_GET['inizio'] : null;
        $to = isset($_GET['fine']) ? (string)$_GET['fine'] : null;

        $model = new PersonalEventModel();
        $events = $model->getUserEvents($userId, $from, $to);

        $formattedEvents = array_map(function (array $ev): array {
            return [
                'idPersonale'      => $ev['id'],
                'nome'             => $ev['title'],
                'dataInizio'       => str_replace(' ', 'T', (string)$ev['start_time']) . 'Z',
                'dataFine'         => str_replace(' ', 'T', (string)$ev['end_time']) . 'Z',
                'tipoAbbreviazione'=> 'Personale',
                'risorse'          => [['aula' => ['descrizione' => $ev['location']]]],
                'isPersonale'      => true,
            ];
        }, $events);

        Http::json($formattedEvents);
    }

    public function savePersonalEvent(): void
    {
        $userId = Http::requireUserId();
        $data = Http::input();

        $title = isset($data['nome']) && is_string($data['nome']) ? trim($data['nome']) : '';
        $location = isset($data['luogo']) && is_string($data['luogo']) ? trim($data['luogo']) : '';
        $startTs = isset($data['dataInizio']) && is_string($data['dataInizio']) ? strtotime($data['dataInizio']) : false;
        $endTs = isset($data['dataFine']) && is_string($data['dataFine']) ? strtotime($data['dataFine']) : false;

        if ($title === '' || $startTs === false || $endTs === false || $endTs <= $startTs) {
            Http::json(['error' => 'Dati evento non validi'], 400);
        }

        $model = new PersonalEventModel();
        $model->createEvent($userId, [
            'title'      => Str::cut($title, 255),
            'start_time' => date('Y-m-d H:i:s', $startTs),
            'end_time'   => date('Y-m-d H:i:s', $endTs),
            'location'   => Str::cut($location !== '' ? $location : 'Non specificato', 255),
        ]);

        Http::json(['status' => 'success']);
    }

    public function deletePersonalEvent(string $eventId): void
    {
        $userId = Http::requireUserId();
        $model = new PersonalEventModel();
        $model->deleteEvent($userId, (int)$eventId);
        Http::json(['status' => 'success']);
    }

    public function getHiddenCourses(): void
    {
        $userId = Http::requireUserId();
        $model = new HiddenCourseModel();
        Http::json($model->getHiddenCourses($userId));
    }

    public function toggleHiddenCourse(): void
    {
        $userId = Http::requireUserId();
        $data = Http::input();

        $courseName = isset($data['course_name']) && is_string($data['course_name'])
            ? Str::cut(trim($data['course_name']), 255)
            : '';

        if ($courseName === '') {
            Http::json(['error' => 'Nome corso mancante'], 400);
        }

        $model = new HiddenCourseModel();
        $result = $model->toggleCourse($userId, strtoupper($courseName));
        Http::json($result);
    }
}
