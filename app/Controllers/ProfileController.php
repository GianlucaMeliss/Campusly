<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\UserModel;
use App\Models\HiddenCourseModel;
use App\Models\GroupModel;

class ProfileController
{
    private UserModel $userModel;
    private HiddenCourseModel $hiddenCourseModel;
    private GroupModel $groupModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->hiddenCourseModel = new HiddenCourseModel();
        $this->groupModel = new GroupModel();
    }

    public function showProfile(): void
    {
        $userId = (int)$_SESSION['user_id'];
        
        $courses = $this->userModel->getUserCourses($userId);
        $hiddenCourses = $this->hiddenCourseModel->getHiddenCourses($userId);
        $groups = $this->groupModel->getUserGroups($userId);

        \App\Core\View::render('pages/profile', [
            'pageTitle' => 'Il Mio Profilo - Campusly',
            'activeMenu' => 'profile',
            'pageCss' => 'profile',
            'user' => ['name' => $_SESSION['user_name']],
            'courses' => $courses,
            'hiddenCourses' => $hiddenCourses,
            'groups' => $groups 
        ]);
    }

    public function removeCourse(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_id'])) {
            $userId = (int)$_SESSION['user_id'];
            $profileId = (int)$_POST['profile_id'];
            $this->userModel->removeUserCourse($userId, $profileId);
            
            header('Location: ' . BASE_PATH . '/profilo?status=removed');
            exit;
        }
    }

    public function syncTheme(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $theme = $data['theme'] ?? 'system';

        if (!is_string($theme) || !in_array($theme, ['light', 'dark', 'system'], true)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Tema non valido']);
            exit;
        }

        // Inseriamo o aggiorniamo la preferenza nella tabella user_preferences
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, theme) VALUES (:uid, :theme)
            ON DUPLICATE KEY UPDATE theme = :theme_update
        ");
        $stmt->execute(['uid' => $userId, 'theme' => $theme, 'theme_update' => $theme]);
        
        $_SESSION['theme'] = $theme;
        echo json_encode(['status' => 'success']);
        exit;
    }
}