<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Http;
use App\Core\Str;

final class OnboardingApiController
{
    public function getCoursesByUniversity(string $uniId): void
    {
        Http::requireUserId();

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, name FROM courses WHERE university_id = :uni_id ORDER BY name ASC");
        $stmt->execute(['uni_id' => (int)$uniId]);

        Http::json($stmt->fetchAll());
    }

    public function getCourseCurriculums(string $courseId): void
    {
        Http::requireUserId();

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, campus_location, year FROM course_curriculums WHERE course_id = :cid ORDER BY year ASC");
        $stmt->execute(['cid' => (int)$courseId]);

        Http::json($stmt->fetchAll());
    }

    private static function cleanLine(string $value, int $max = 150): string
    {
        $clean = str_replace(["\r", "\n"], ' ', trim($value));
        return Str::cut($clean, $max);
    }

    public function submitOnboardingRequest(): void
    {
        $userId = Http::requireUserId();
        $data = Http::input();

        $type = (string)($data['type'] ?? '');
        $name = self::cleanLine((string)($data['name'] ?? ''), 150);
        $courseExtra = self::cleanLine((string)($data['course_extra'] ?? ''), 150);
        $contextUni = self::cleanLine((string)($data['context_uni'] ?? ''), 150);
        $contextCourse = self::cleanLine((string)($data['context_course'] ?? ''), 150);

        if ($name === '') {
            Http::json(['error' => 'Dato principale mancante'], 400);
        }

        $requestDataToSave = $name;
        $tipoTesto = 'Segnalazione';
        $reqType = 'new_course';

        if ($type === 'uni') {
            $reqType = 'new_university';
            $tipoTesto = 'Università';
            if ($courseExtra !== '') {
                $requestDataToSave .= " (Corso richiesto: {$courseExtra})";
            }
        } elseif ($type === 'course') {
            $tipoTesto = 'Corso (per Università esistente)';
            $requestDataToSave = "Corso richiesto: {$name} | Università: {$contextUni}";
        } elseif ($type === 'curriculum') {
            $tipoTesto = 'Curriculum (Sede/Anno mancante)';
            $requestDataToSave = "Dettagli mancanti: {$name} | Corso: {$contextCourse} | Università: {$contextUni}";
        }

        $db = Database::getInstance();
        $stmtUser = $db->prepare("SELECT email FROM users WHERE id = :uid");
        $stmtUser->execute(['uid' => $userId]);
        $user = $stmtUser->fetch();
        $userEmail = $user ? (string)$user['email'] : 'Email sconosciuta';

        $stmt = $db->prepare("INSERT INTO onboarding_requests (user_id, request_type, request_data) VALUES (:uid, :type, :data)");
        $stmt->execute([
            'uid'  => $userId,
            'type' => $reqType,
            'data' => $requestDataToSave,
        ]);

        $appConfig = require BASEPATH . '/config/app.php';
        $to = (string)($appConfig['mail']['contact_email'] ?? 'info@campusly.it');
        $from = self::cleanLine((string)($appConfig['mail']['from'] ?? 'noreply@campusly.it'), 120);
        $subject = "🔔 Campusly - Segnalazione Onboarding: {$tipoTesto}";

        $message = "Ciao!\nUno studente ha effettuato una segnalazione durante la configurazione.\n\n";
        $message .= "Richiedente: {$userEmail}\n";
        $message .= "Tipo Segnalazione: {$tipoTesto}\n\n";

        if ($type === 'uni') {
            $message .= "Ateneo richiesto: {$name}\n";
            if ($courseExtra !== '') {
                $message .= "Corso associato: {$courseExtra}\n";
            }
        } elseif ($type === 'course') {
            $message .= "Università selezionata: {$contextUni}\n";
            $message .= "Corso mancante segnalato: {$name}\n";
        } elseif ($type === 'curriculum') {
            $message .= "Università: {$contextUni}\n";
            $message .= "Corso selezionato: {$contextCourse}\n";
            $message .= "Dettagli mancanti segnalati dall'utente: {$name}\n";
        }

        $message .= "\nPuoi verificare la richiesta nel database.\n";

        $headers = "From: {$from}\r\nReply-To: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        @mail($to, $subject, $message, $headers);

        Http::json(['status' => 'success']);
    }
}
