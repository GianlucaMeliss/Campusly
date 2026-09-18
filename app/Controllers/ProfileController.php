<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\UserModel;
use App\Models\HiddenCourseModel;

class ProfileController
{
    private UserModel $userModel;
    private HiddenCourseModel $hiddenCourseModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->hiddenCourseModel = new HiddenCourseModel();
    }

    public function showProfile(): void
    {
        $userId = (int)$_SESSION['user_id'];
        
        // Recuperiamo i dati attuali del corso
        $courseConfig = $this->userModel->getUserCourseConfig($userId);
        $extConfig = $courseConfig ? json_decode($courseConfig['external_course_id'], true) : [];
        
        // Recuperiamo i corsi nascosti
        $hiddenCourses = $this->hiddenCourseModel->getHiddenCourses($userId);

        View::render('pages/profile', [
            'pageTitle' => 'Il Mio Profilo - Campusly',
            'user' => [
                'name' => $_SESSION['user_name'],
                // In un'app reale prenderemmo anche l'email dal DB
            ],
            'course' => [
                'name' => $courseConfig['name'] ?? '',
                'linkId' => $extConfig['linkCalendarioId'] ?? '',
                'clienteId' => $extConfig['clienteId'] ?? ''
            ],
            'hiddenCourses' => $hiddenCourses
        ]);
    }

    public function updateCourse(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        $userId = (int)$_SESSION['user_id'];
        $uniId = 1; // Manteniamo Insubria fisso per ora
        $courseName = trim($_POST['course_name']);
        
        $configJson = json_encode([
            'linkCalendarioId' => trim($_POST['link_calendario_id']),
            'clienteId' => trim($_POST['cliente_id'])
        ]);

        // Sovrascriviamo o creiamo il nuovo profilo accademico
        $this->userModel->saveAcademicProfile($userId, $uniId, $courseName, $configJson);

        // Reindirizziamo con un flag di successo
        header('Location: ' . BASE_PATH . '/profilo?status=updated');
        exit;
    }

    // API per salvare il tema (Light/Dark)
    public function syncTheme(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $theme = $data['theme'] ?? 'system';

        // Inseriamo o aggiorniamo la preferenza nella tabella user_preferences
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, theme) VALUES (:uid, :theme)
            ON DUPLICATE KEY UPDATE theme = :theme_update
        ");
        $stmt->execute(['uid' => $userId, 'theme' => $theme, 'theme_update' => $theme]);
        
        echo json_encode(['status' => 'success']);
        exit;
    }
}