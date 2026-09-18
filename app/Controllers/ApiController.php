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

        $dataInizio = $_GET['inizio'] ?? date('Y-m-d\T00:00:00.000\Z');
        $dataFine = $_GET['fine'] ?? date('Y-m-d\T23:59:59.000\Z', strtotime('+7 days'));

        // IN FUTURO: Qui leggeremo i dati dal DB basandoci sull'utente loggato ($_SESSION['user_id']).
        // PER ORA: Mockiamo la configurazione del tuo corso attuale di Ostetricia per testare l'Adapter.
        $courseConfig = [
            'linkCalendarioId' => '6a69b9d9c4a67700195312e8',
            'clienteId' => '59f05192a635f443422fe8fd',
            'adapter' => \App\Adapters\CinecaAdapter::class
        ];

        // LOGICA DI CACHE SERVER-SIDE (solo per evitare sovraccarichi al Cineca)
        $dataPulita = substr(preg_replace('/[^0-9]/', '', $dataInizio), 0, 8);
        $cacheDir = BASEPATH . '/data/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $cacheFile = $cacheDir . '/settimana_' . $dataPulita . '_' . $courseConfig['linkCalendarioId'] . '.json';
        $cacheTime = 300; // 5 minuti

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
            header("X-Cache-Status: HIT-MICROCACHE");
            echo file_get_contents($cacheFile);
            exit;
        }

        header("X-Cache-Status: MISS-FETCHING-API");

        try {
            // Istanziamo dinamicamente l'Adapter salvato nel database
            $adapterClass = $courseConfig['adapter'];
            /** @var \App\Adapters\UniversityAdapterInterface $adapter */
            $adapter = new $adapterClass();

            $eventi = $adapter->getSchedule($dataInizio, $dataFine, $courseConfig);

            $jsonResponse = json_encode($eventi);
            
            // Salviamo la cache su file system
            file_put_contents($cacheFile, $jsonResponse);
            
            echo $jsonResponse;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Aggiungi queste use in alto nel file ApiController.php
    // use App\Models\PersonalEventModel;

    public function getPersonalEvents(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        
        // Simulo l'utente loggato (da sostituire con $_SESSION['user_id'])
        $userId = 1; 

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
        
        $userId = 1; // Simulo l'utente loggato
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

    public function deletePersonalEvent(string $eventId): void
    {
        $userId = 1; // Simulo l'utente loggato
        $model = new \App\Models\PersonalEventModel();
        $model->deleteEvent($userId, (int)$eventId);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function getHiddenCourses(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        $userId = 1; // Simulo l'utente loggato

        $model = new \App\Models\HiddenCourseModel();
        $courses = $model->getHiddenCourses($userId);

        echo json_encode($courses);
        exit;
    }

    public function toggleHiddenCourse(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        $userId = 1; // Simulo l'utente loggato
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
}