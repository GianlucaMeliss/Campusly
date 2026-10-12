<?php

declare(strict_types=1);

namespace App\Controllers;

class ApiController
{
    private const PRIVACY_LEVELS = ['transparent', 'logistical', 'opaque'];

    public function sendContact(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            $baseUrl = strtok($referer, '?');

            $timeBetweenRequests = 60;
            if (isset($_SESSION['last_submission_time'])) {
                $secondsSinceLast = time() - $_SESSION['last_submission_time'];
                if ($secondsSinceLast < $timeBetweenRequests) {
                    header("Location: " . $baseUrl . "?status=error&msg=ratelimit");
                    exit;
                }
            }

            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                header("Location: " . $baseUrl . "?status=error&msg=csrf");
                exit;
            }

            $appConfig = require BASEPATH . '/config/app.php';
            $to = $appConfig['mail']['contact_email'] ?? 'noreply@localhost';
            $subject = $appConfig['mail']['contact_subject'] ?? 'Nuovo contatto';

            $message = "Hai ricevuto una nuova richiesta di contatto dal sito web.\n\n";
            $message .= "👤 DETTAGLI CONTATTO\n";
            $message .= "-----------------------------------\n";
            
            $hasData = false;

            foreach ($_POST as $key => $value) {
                if ($key === 'csrf_token') continue;
                
                $cleanValue = htmlspecialchars(trim((string)$value));
                if (!empty($cleanValue)) {
                    $label = ucfirst(str_replace('_', ' ', $key));
                    $message .= "$label: $cleanValue\n";
                    $hasData = true;
                }
            }

            $message .= "-----------------------------------\n";

            if (!$hasData) {
                header("Location: " . $baseUrl . "?status=error&msg=empty");
                exit;
            }

            $domain = $_SERVER['HTTP_HOST'];
            $headers = "From: noreply@$domain\r\n"; 
            $headers .= "Reply-To: noreply@$domain\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($to, $subject, $message, $headers)) {
                $_SESSION['last_submission_time'] = time();
                header("Location: " . $baseUrl . "?status=success");
                exit;
            } else {
                header("Location: " . $baseUrl . "?status=error&msg=server");
                exit;
            }

        } else {
            header("Location: /");
            exit;
        }
    }

    public function logCookie(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $timeBetweenRequests = 10;
            if (isset($_SESSION['last_cookie_log_time'])) {
                $secondsSinceLast = time() - $_SESSION['last_cookie_log_time'];
                if ($secondsSinceLast < $timeBetweenRequests) {
                    http_response_code(429);
                    echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded.']);
                    return;
                }
            }

            $jsonPayload = file_get_contents('php://input');
            $data = json_decode($jsonPayload, true);

            if (!isset($data['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
                return;
            }

            if (isset($data['consent_status']) && isset($data['uuid'])) {
                $logDir = BASE_PATH . '/app/logs';
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                $logFile = $logDir . '/cookie_consents.csv';
                $isNewFile = !file_exists($logFile);
                
                $timestamp = date('Y-m-d H:i:s');
                $uuid = htmlspecialchars(trim($data['uuid']));
                $status = htmlspecialchars(trim($data['consent_status']));
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $anonIp = preg_replace('/[0-9]+$/', 'xxx', $ip);

                $file = fopen($logFile, 'a');
                
                if ($file) {
                    if ($isNewFile) fputcsv($file, ['Timestamp', 'UUID', 'Consent_Status', 'Anon_IP', 'User_Agent']);
                    fputcsv($file, [$timestamp, $uuid, $status, $anonIp, $userAgent]);
                    fclose($file);
                    
                    $_SESSION['last_cookie_log_time'] = time();
                    
                    http_response_code(200);
                    echo json_encode(['status' => 'success']);
                } else {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Impossibile scrivere il log.']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Dati mancanti.']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Metodo non consentito.']);
        }
    }

    public function getCalendarEvents(): void
    {
        header("Content-Type: application/json; charset=UTF-8");

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Utente non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];

        $dataInizio = $_GET['inizio'] ?? date('Y-m-d\T00:00:00.000\Z');
        $dataFine = $_GET['fine'] ?? date('Y-m-d\T23:59:59.000\Z', strtotime('+7 days'));

        $userModel = new \App\Models\UserModel();
        $userCourses = $userModel->getUserCourses($userId);

        if (empty($userCourses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nessun corso configurato']);
            exit;
        }

        $tuttiGliEventi = [];
        $hashCorsi = md5(serialize(array_column($userCourses, 'external_course_id')));
        $dataPulita = substr(preg_replace('/[^0-9]/', '', $dataInizio), 0, 8);
        
        $cacheDir = BASE_PATH . '/data/cache';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
        $cacheFile = $cacheDir . '/settimana_' . $dataPulita . '_' . $hashCorsi . '.json';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
            header("X-Cache-Status: HIT-MICROCACHE");
            echo file_get_contents($cacheFile);
            exit;
        }

        header("X-Cache-Status: MISS-FETCHING-API");

        try {
            foreach ($userCourses as $courseData) {
                $extConfig = json_decode($courseData['external_course_id'], true);
                if (isset($extConfig['linkCalendarioId']) && $extConfig['linkCalendarioId'] === 'AUTO') continue; 

                $adapterClass = $courseData['adapter_class'];
                if (class_exists($adapterClass)) {
                    $adapter = new $adapterClass();
                    $eventiCorso = $adapter->getSchedule($dataInizio, $dataFine, $extConfig);
                    $tuttiGliEventi = array_merge($tuttiGliEventi, $eventiCorso);
                }
            }

            $jsonResponse = json_encode($tuttiGliEventi);
            file_put_contents($cacheFile, $jsonResponse);
            echo $jsonResponse;

        } catch (\Exception $e) {
            error_log('[getCalendarEvents] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Servizio orari temporaneamente non disponibile']);
        }
        exit;
    }

    public function getCoursesByUniversity(string $uniId): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT id, name FROM courses WHERE university_id = :uni_id ORDER BY name ASC");
        $stmt->execute(['uni_id' => (int)$uniId]);
        
        echo json_encode($stmt->fetchAll());
        exit;
    }

    public function getPersonalEvents(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];

        $model = new \App\Models\PersonalEventModel();
        $events = $model->getUserEvents($userId);

        $formattedEvents = array_map(function($ev) {
            return [
                'idPersonale' => $ev['id'],
                'nome' => $ev['title'],
                'dataInizio' => str_replace(' ', 'T', $ev['start_time']) . 'Z',
                'dataFine' => str_replace(' ', 'T', $ev['end_time']) . 'Z',
                'tipoAbbreviazione' => "Personale",
                'risorse' => [['aula' => ['descrizione' => $ev['location']]]],
                'isPersonale' => true
            ];
        }, $events);

        echo json_encode($formattedEvents);
        exit;
    }

    public function savePersonalEvent(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        $title = is_array($data) && isset($data['nome']) && is_string($data['nome']) ? trim($data['nome']) : '';
        $location = is_array($data) && isset($data['luogo']) && is_string($data['luogo']) ? trim($data['luogo']) : '';
        $startTs = is_array($data) && isset($data['dataInizio']) && is_string($data['dataInizio']) ? strtotime($data['dataInizio']) : false;
        $endTs = is_array($data) && isset($data['dataFine']) && is_string($data['dataFine']) ? strtotime($data['dataFine']) : false;

        if ($title === '' || $startTs === false || $endTs === false || $endTs <= $startTs) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati evento non validi']);
            exit;
        }

        $model = new \App\Models\PersonalEventModel();
        $model->createEvent($userId, [
            'title' => \App\Core\Str::cut($title, 255),
            'start_time' => date('Y-m-d H:i:s', $startTs),
            'end_time' => date('Y-m-d H:i:s', $endTs),
            'location' => \App\Core\Str::cut($location !== '' ? $location : 'Non specificato', 255)
        ]);

        echo json_encode(['status' => 'success']);
        exit;
    }

    public function getCourseCurriculums(string $courseId): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT id, campus_location, year FROM course_curriculums WHERE course_id = :cid ORDER BY year ASC");
        $stmt->execute(['cid' => (int)$courseId]);
        
        echo json_encode($stmt->fetchAll());
        exit;
    }

    public function deletePersonalEvent(string $eventId): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];
        $model = new \App\Models\PersonalEventModel();
        $model->deleteEvent($userId, (int)$eventId);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function getHiddenCourses(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];

        $model = new \App\Models\HiddenCourseModel();
        $courses = $model->getHiddenCourses($userId);

        echo json_encode($courses);
        exit;
    }

    public function toggleHiddenCourse(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];

        $data = json_decode(file_get_contents('php://input'), true);

        $courseName = is_array($data) && isset($data['course_name']) && is_string($data['course_name'])
            ? \App\Core\Str::cut(trim($data['course_name']), 255)
            : '';

        if ($courseName !== '') {
            $model = new \App\Models\HiddenCourseModel();
            $result = $model->toggleCourse($userId, strtoupper($courseName));
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Nome corso mancante']);
        }
        exit;
    }

    public function submitOnboardingRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        header("Content-Type: application/json; charset=UTF-8");
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF non valido']);
            exit;
        }

        $type = $data['type'] ?? '';
        $name = trim(htmlspecialchars($data['name'] ?? ''));
        $courseExtra = trim(htmlspecialchars($data['course_extra'] ?? '')); 
        $contextUni = trim(htmlspecialchars($data['context_uni'] ?? '')); 
        $contextCourse = trim(htmlspecialchars($data['context_course'] ?? ''));
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Dato principale mancante']);
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $requestDataToSave = $name;
        $tipoTesto = '';
        $reqType = 'new_course'; 

        if ($type === 'uni') {
            $reqType = 'new_university';
            $tipoTesto = 'Università';
            if (!empty($courseExtra)) $requestDataToSave .= " (Corso richiesto: $courseExtra)";
        } elseif ($type === 'course') {
            $tipoTesto = 'Corso (per Università esistente)';
            $requestDataToSave = "Corso richiesto: $name | Università: $contextUni";
        } elseif ($type === 'curriculum') {
            $tipoTesto = 'Curriculum (Sede/Anno mancante)';
            $requestDataToSave = "Dettagli mancanti: $name | Corso: $contextCourse | Università: $contextUni";
        }

        $db = \App\Core\Database::getInstance();
        $stmtUser = $db->prepare("SELECT email FROM users WHERE id = :uid");
        $stmtUser->execute(['uid' => $userId]);
        $user = $stmtUser->fetch();
        $userEmail = $user ? $user['email'] : 'Email sconosciuta';
        
        $stmt = $db->prepare("INSERT INTO onboarding_requests (user_id, request_type, request_data) VALUES (:uid, :type, :data)");
        $stmt->execute(['uid' => $userId, 'type' => $reqType, 'data' => $requestDataToSave]);
        
        $appConfig = require BASEPATH . '/config/app.php';
        $to = $appConfig['mail']['contact_email'] ?? 'admin@localhost';
        $subject = "🔔 Campusly - Segnalazione Onboarding: $tipoTesto";
        
        $message = "Ciao!\nUno studente ha effettuato una segnalazione durante la configurazione.\n\n";
        $message .= "Richiedente: $userEmail\n";
        $message .= "Tipo Segnalazione: $tipoTesto\n\n";
        
        if ($type === 'uni') {
            $message .= "Ateneo richiesto: $name\n";
            if (!empty($courseExtra)) $message .= "Corso associato: $courseExtra\n";
        } elseif ($type === 'course') {
            $message .= "Università selezionata: $contextUni\n";
            $message .= "Corso mancante segnalato: $name\n";
        } elseif ($type === 'curriculum') {
            $message .= "Università: $contextUni\n";
            $message .= "Corso selezionato: $contextCourse\n";
            $message .= "Dettagli mancanti segnalati dall'utente: $name\n";
        }
        
        $message .= "\nPuoi verificare la richiesta nel database.\n";
        
        $domain = $_SERVER['HTTP_HOST'] ?? 'campusly.it';
        $headers = "From: noreply@$domain\r\nReply-To: noreply@$domain\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        
        mail($to, $subject, $message, $headers);
        
        echo json_encode(['status' => 'success']);
        exit;
    }

    // --- SEZIONE GRUPPI (Aggiunte) ---

    public function createGroup(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF non valido']);
            exit;
        }

        $name = is_array($data) && isset($data['name']) && is_string($data['name']) ? \App\Core\Str::cut(trim($data['name']), 100) : '';
        $privacy = $data['privacy_level'] ?? 'transparent';
        
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Nome gruppo mancante']);
            exit;
        }

        if (!is_string($privacy) || !in_array($privacy, self::PRIVACY_LEVELS, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Livello di privacy non valido']);
            exit;
        }

        $model = new \App\Models\GroupModel();
        $result = $model->createGroup((int)$_SESSION['user_id'], $name, $privacy);
        echo json_encode($result);
        exit;
    }

    public function joinGroup(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF non valido']);
            exit;
        }

        $code = is_array($data) && isset($data['invite_code']) && is_string($data['invite_code'])
            ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['invite_code']))
            : '';
        $privacy = $data['privacy_level'] ?? 'logistical';
        
        if ($code === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Codice mancante']);
            exit;
        }

        if (!is_string($privacy) || !in_array($privacy, self::PRIVACY_LEVELS, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Livello di privacy non valido']);
            exit;
        }

        $model = new \App\Models\GroupModel();
        $result = $model->joinGroup((int)$_SESSION['user_id'], $code, $privacy);
        echo json_encode($result);
        exit;
    }

    public function getGroupCalendar(string $groupId): void
    {
        header("Content-Type: application/json; charset=UTF-8");

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Utente non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];
        $groupId = (int)$groupId;

        $groupModel = new \App\Models\GroupModel();
        $members = $groupModel->getGroupMembers($groupId);
        
        $isMember = false;
        foreach ($members as $m) {
            if ((int)$m['id'] === $userId) $isMember = true;
        }

        if (!$isMember) {
            http_response_code(403);
            echo json_encode(['error' => 'Accesso negato al gruppo']);
            exit;
        }

        $dataInizio = $_GET['inizio'] ?? date('Y-m-d\T00:00:00.000\Z');
        $dataFine = $_GET['fine'] ?? date('Y-m-d\T23:59:59.000\Z', strtotime('+7 days'));

        $megaEvents = [];
        $userModel = new \App\Models\UserModel();
        $hiddenCourseModel = new \App\Models\HiddenCourseModel();
        $personalEventModel = new \App\Models\PersonalEventModel();

        foreach ($members as $member) {
            $memberId = (int)$member['id'];
            $privacy = $member['privacy_level'];
            $isMe = ($memberId === $userId);

            $hiddenCourses = $hiddenCourseModel->getHiddenCourses($memberId);
            $userCourses = $userModel->getUserCourses($memberId);
            $eventiUniv = $this->getInternalUserEventsCached($memberId, $userCourses, $dataInizio, $dataFine);
            $eventiPers = $personalEventModel->getUserEvents($memberId);
            
            foreach ($eventiUniv as $ev) {
                if (isset($ev['stato']) && $ev['stato'] === 'A') continue;
                $nomeCorso = strtoupper($ev['nome'] ?? '');
                $isHidden = false;
                foreach ($hiddenCourses as $hc) {
                    if (strpos($nomeCorso, strtoupper($hc)) !== false) $isHidden = true;
                }
                if ($isHidden) continue;

                if (!$isMe) {
                    if ($privacy === 'opaque') {
                        $ev['nome'] = "Occupato";
                        unset($ev['risorse']);
                        unset($ev['dettagliDidattici']);
                    } elseif ($privacy === 'logistical') {
                        $ev['nome'] = "Occupato (" . $member['first_name'] . ")";
                        unset($ev['dettagliDidattici']);
                        if (isset($ev['risorse'])) {
                            foreach ($ev['risorse'] as &$r) unset($r['docente']);
                        }
                    } elseif ($privacy === 'transparent') {
                        $ev['nome'] = $ev['nome'] . " (" . $member['first_name'] . ")";
                    }
                }
                $ev['member_id'] = $memberId; 
                $ev['is_me'] = $isMe;
                $megaEvents[] = $ev;
            }

            foreach ($eventiPers as $ep) {
                $evFormat = [
                    'idPersonale' => $ep['id'],
                    'dataInizio' => str_replace(' ', 'T', $ep['start_time']) . 'Z',
                    'dataFine' => str_replace(' ', 'T', $ep['end_time']) . 'Z',
                    'isPersonale' => true,
                    'member_id' => $memberId,
                    'is_me' => $isMe
                ];

                if ($isMe || $privacy === 'transparent') {
                    $evFormat['nome'] = $isMe ? $ep['title'] : $ep['title'] . " (" . $member['first_name'] . ")";
                    $evFormat['luogo'] = $ep['location'];
                } else {
                    $evFormat['nome'] = "Occupato (" . $member['first_name'] . ")";
                    $evFormat['luogo'] = "Non condiviso";
                }
                
                $megaEvents[] = $evFormat;
            }
        }

        // Raccogliamo i nomi dei membri per il frontend
        $groupInfo = [];
        foreach ($members as $m) {
            $groupInfo[$m['id']] = $m['first_name'];
        }

        // Restituiamo un oggetto strutturato
        echo json_encode([
            'members' => $groupInfo,
            'events' => $megaEvents
        ]);
        exit;
    }

    private function getInternalUserEventsCached(int $userId, array $userCourses, string $dataInizio, string $dataFine): array
    {
        if (empty($userCourses)) return [];

        $hashCorsi = md5(serialize(array_column($userCourses, 'external_course_id')));
        $dataPulita = substr(preg_replace('/[^0-9]/', '', $dataInizio), 0, 8);
        $cacheDir = BASE_PATH . '/data/cache';
        $cacheFile = $cacheDir . '/settimana_' . $dataPulita . '_' . $hashCorsi . '.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            return is_array($data) ? $data : [];
        }

        $tuttiGliEventi = [];
        try {
            foreach ($userCourses as $courseData) {
                $extConfig = json_decode($courseData['external_course_id'], true);
                if (isset($extConfig['linkCalendarioId']) && $extConfig['linkCalendarioId'] === 'AUTO') continue;

                $adapterClass = $courseData['adapter_class'];
                if (class_exists($adapterClass)) {
                    $adapter = new $adapterClass();
                    $eventiCorso = $adapter->getSchedule($dataInizio, $dataFine, $extConfig);
                    $tuttiGliEventi = array_merge($tuttiGliEventi, $eventiCorso);
                }
            }
            if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
            file_put_contents($cacheFile, json_encode($tuttiGliEventi));
        } catch (\Exception $e) {
            if (file_exists($cacheFile)) {
                $data = json_decode(file_get_contents($cacheFile), true);
                return is_array($data) ? $data : [];
            }
        }

        return $tuttiGliEventi;
    }
}