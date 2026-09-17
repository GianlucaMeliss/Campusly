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
}