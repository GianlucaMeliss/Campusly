<?php

declare(strict_types=1);

namespace App\Controllers;
/**
 * * Questo controller gestisce le richieste POST, i form e le chiamate AJAX.
 * TUTTI i metodi aggiunti in questa classe DEVONO rispettare questo contratto:
 * * 1. Validazione CSRF: Nessun payload passa senza check su $_SESSION['csrf_token'].
 * 2. Rate-Limiting: Ogni endpoint deve limitare gli abusi con timestamp in sessione.
 * 3. Risposte Standard: Usare Redirect (con query string) per i form HTML classici, 
 * oppure risposte JSON rigorose (con HTTP status code) per chiamate fetch/XHR.
 */
class ApiController
{
    public function sendContact(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            $baseUrl = strtok($referer, '?');

            // 1. RATE LIMITING
            $timeBetweenRequests = 60;
            if (isset($_SESSION['last_submission_time'])) {
                $secondsSinceLast = time() - $_SESSION['last_submission_time'];
                if ($secondsSinceLast < $timeBetweenRequests) {
                    header("Location: " . $baseUrl . "?status=error&msg=ratelimit");
                    exit;
                }
            }

            // 2. VERIFICA TOKEN CSRF (assicurati di averlo nel tuo form HTML)
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                header("Location: " . $baseUrl . "?status=error&msg=csrf");
                exit;
            }

            // 3. RECUPERO CONFIGURAZIONI MAIL
            $appConfig = require BASEPATH . '/config/app.php';
            $to = $appConfig['mail']['contact_email'] ?? 'noreply@localhost';
            $subject = $appConfig['mail']['contact_subject'] ?? 'Nuovo contatto';

            // 4. CREAZIONE DINAMICA DEL MESSAGGIO
            $message = "Hai ricevuto una nuova richiesta di contatto dal sito web.\n\n";
            $message .= "👤 DETTAGLI CONTATTO\n";
            $message .= "-----------------------------------\n";
            
            $hasData = false;

            // Cicla tutti i campi inviati dal form ignorando il token di sicurezza
            foreach ($_POST as $key => $value) {
                if ($key === 'csrf_token') continue;
                
                $cleanValue = htmlspecialchars(trim((string)$value));
                if (!empty($cleanValue)) {
                    // Trasforma "nome_azienda" in "Nome azienda" per l'email
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

            // 5. INVIO EFFETTIVO
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
            
            // 1. RATE LIMITING INDIPENDENTE (Max 1 richiesta ogni 10 secondi)
            $timeBetweenRequests = 10;
            if (isset($_SESSION['last_cookie_log_time'])) {
                $secondsSinceLast = time() - $_SESSION['last_cookie_log_time'];
                if ($secondsSinceLast < $timeBetweenRequests) {
                    http_response_code(429); // 429 Too Many Requests
                    echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded.']);
                    return;
                }
            }

            $jsonPayload = file_get_contents('php://input');
            $data = json_decode($jsonPayload, true);

            // 2. VERIFICA TOKEN CSRF
            if (!isset($data['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
                http_response_code(403); // 403 Forbidden
                echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
                return;
            }

            // 3. ESECUZIONE DELLA LOGICA
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
                    if ($isNewFile) {
                        fputcsv($file, ['Timestamp', 'UUID', 'Consent_Status', 'Anon_IP', 'User_Agent']);
                    }
                    
                    fputcsv($file, [$timestamp, $uuid, $status, $anonIp, $userAgent]);
                    fclose($file);
                    
                    // Aggiorna il tempo dell'ultima richiesta solo se è andata a buon fine
                    $_SESSION['last_cookie_log_time'] = time();
                    
                    http_response_code(200);
                    echo json_encode(['status' => 'success']);
                } else {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Impossibile scrivere il log.']);
                }
            } else {
                http_response_code(400); // 400 Bad Request
                echo json_encode(['status' => 'error', 'message' => 'Dati mancanti.']);
            }
        } else {
            http_response_code(405); // 405 Method Not Allowed
            echo json_encode(['status' => 'error', 'message' => 'Metodo non consentito.']);
        }
    }

    public function getCalendarEvents(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");

        // 1. Blocco Sicurezza: chi non è loggato non passa
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
        
        // Generiamo una chiave di cache che tenga conto di TUTTI i corsi dell'utente
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
                if (isset($extConfig['linkCalendarioId']) && $extConfig['linkCalendarioId'] === 'AUTO') continue; // Salta i non ancora mappati

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
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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

        // Sfruttiamo il Singleton del DB già presente nel core
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT id, name FROM courses WHERE university_id = :uni_id ORDER BY name ASC");
        $stmt->execute(['uni_id' => (int)$uniId]);
        
        $courses = $stmt->fetchAll();

        echo json_encode($courses);
        exit;
    }

    public function getPersonalEvents(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorizzato
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];

        $model = new \App\Models\PersonalEventModel();
        $events = $model->getUserEvents($userId);

        // Formattiamo i dati per farli digerire al frontend come se fossero eventi Cineca
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorizzato
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data) {
            $model = new \App\Models\PersonalEventModel();
            // Convertiamo le date ISO JS (es. 2026-09-18T10:00:00.000Z) in formato MySQL (YYYY-MM-DD HH:MM:SS)
            $startTime = date('Y-m-d H:i:s', strtotime($data['dataInizio']));
            $endTime = date('Y-m-d H:i:s', strtotime($data['dataFine']));
            
            $model->createEvent($userId, [
                'title' => $data['nome'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'location' => $data['luogo']
            ]);
            
            echo json_encode(['status' => 'success']);
        }
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
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorizzato
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
            http_response_code(401); // Non autorizzato
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorizzato
            echo json_encode(['error' => 'Non autenticato']);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];

        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['course_name'])) {
            $model = new \App\Models\HiddenCourseModel();
            $result = $model->toggleCourse($userId, strtoupper(trim($data['course_name'])));
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
        
        // Verifica CSRF
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
        
        // Formattazione dati in base al tipo di richiesta
        $requestDataToSave = $name;
        $tipoTesto = '';
        $reqType = 'new_course'; // Default DB enum

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
        
        // Salvataggio nel Database
        $stmt = $db->prepare("INSERT INTO onboarding_requests (user_id, request_type, request_data) VALUES (:uid, :type, :data)");
        $stmt->execute(['uid' => $userId, 'type' => $reqType, 'data' => $requestDataToSave]);
        
        // Costruzione Email
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

    // --- SEZIONE GRUPPI ---

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

        $name = trim(htmlspecialchars($data['name'] ?? ''));
        $privacy = $data['privacy_level'] ?? 'transparent';
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome gruppo mancante']);
            exit;
        }

        $model = new \App\Models\GroupModel();
        $result = $model->createGroup((int)$_SESSION['user_id'], $name, $privacy);
        echo json_encode($result);
        exit;
    }

    public function joinGroup(int $userId, string $inviteCode, string $privacyLevel = 'logistical'): array
    {
        $stmt = $this->db->prepare("SELECT id FROM study_groups WHERE invite_code = :code");
        $stmt->execute(['code' => $inviteCode]);
        $group = $stmt->fetch();

        if (!$group) {
            return ['status' => 'error', 'message' => 'Codice invito non valido'];
        }

        // Usiamo due parametri distinti (:privacy1 e :privacy2) per evitare il crash PDO
        $stmtIns = $this->db->prepare("
            INSERT INTO group_members (group_id, user_id, privacy_level) 
            VALUES (:group_id, :user_id, :privacy1)
            ON DUPLICATE KEY UPDATE privacy_level = :privacy2
        ");
        
        $stmtIns->execute([
            'group_id' => $group['id'],
            'user_id' => $userId,
            'privacy1' => $privacyLevel,
            'privacy2' => $privacyLevel
        ]);

        return ['status' => 'success', 'group_id' => $group['id']];
    }

    public function getGroupCalendar(string $groupId): void
    {
        header("Access-Control-Allow-Origin: *");
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
        
        // Controllo di sicurezza: l'utente che fa la richiesta fa parte del gruppo?
        $isMember = false;
        foreach ($members as $m) {
            if ($m['id'] === $userId) $isMember = true;
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

            // 1. Recupera corsi nascosti dell'utente
            $hiddenCourses = $hiddenCourseModel->getHiddenCourses($memberId);
            
            // 2. Recupera eventi universitari dalla Cache (o li genera se non esistono)
            $userCourses = $userModel->getUserCourses($memberId);
            $eventiUniv = $this->getInternalUserEventsCached($memberId, $userCourses, $dataInizio, $dataFine);
            
            // 3. Recupera eventi personali
            $eventiPers = $personalEventModel->getUserEvents($memberId);
            
            // Filtro e Merge Eventi Universitari
            foreach ($eventiUniv as $ev) {
                // Salta gli eventi annullati o i corsi nascosti per questo utente
                if (isset($ev['stato']) && $ev['stato'] === 'A') continue;
                $nomeCorso = strtoupper($ev['nome'] ?? '');
                $isHidden = false;
                foreach ($hiddenCourses as $hc) {
                    if (strpos($nomeCorso, strtoupper($hc)) !== false) $isHidden = true;
                }
                if ($isHidden) continue;

                // Applica Privacy Mask se NON sono io
                if (!$isMe) {
                    if ($privacy === 'opaque') {
                        $ev['nome'] = "Occupato";
                        unset($ev['risorse']);
                        unset($ev['dettagliDidattici']);
                    } elseif ($privacy === 'logistical') {
                        $ev['nome'] = "Occupato (" . $member['first_name'] . ")";
                        unset($ev['dettagliDidattici']);
                        // Manteniamo le risorse per sapere la sede, ma togliamo il prof per privacy
                        if (isset($ev['risorse'])) {
                            foreach ($ev['risorse'] as &$r) unset($r['docente']);
                        }
                    } elseif ($privacy === 'transparent') {
                        $ev['nome'] = $ev['nome'] . " (" . $member['first_name'] . ")";
                    }
                }
                $ev['member_id'] = $memberId; // Aggiungiamo un flag per riconoscere il proprietario nel frontend
                $megaEvents[] = $ev;
            }

            // Filtro e Merge Eventi Personali
            foreach ($eventiPers as $ep) {
                $evFormat = [
                    'idPersonale' => $ep['id'],
                    'dataInizio' => str_replace(' ', 'T', $ep['start_time']) . 'Z',
                    'dataFine' => str_replace(' ', 'T', $ep['end_time']) . 'Z',
                    'isPersonale' => true,
                    'member_id' => $memberId
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

        echo json_encode($megaEvents);
        exit;
    }

    private function getInternalUserEventsCached(int $userId, array $userCourses, string $dataInizio, string $dataFine): array
    {
        if (empty($userCourses)) return [];

        $hashCorsi = md5(serialize(array_column($userCourses, 'external_course_id')));
        $dataPulita = substr(preg_replace('/[^0-9]/', '', $dataInizio), 0, 8);
        $cacheDir = BASE_PATH . '/data/cache';
        $cacheFile = $cacheDir . '/settimana_' . $dataPulita . '_' . $hashCorsi . '.json';

        // Se la cache è valida (meno di 5 minuti), usala
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            return is_array($data) ? $data : [];
        }

        // Altrimenti, recupera le API per questo utente
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
            // Se fallisce, usiamo la cache vecchia (stale) se esiste per non rompere il gruppo
            if (file_exists($cacheFile)) {
                $data = json_decode(file_get_contents($cacheFile), true);
                return is_array($data) ? $data : [];
            }
        }

        return $tuttiGliEventi;
    }
}