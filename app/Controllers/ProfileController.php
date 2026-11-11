<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\View;
use App\Models\UserModel;
use App\Models\HiddenCourseModel;
use App\Models\GroupModel;
use App\Core\Database;

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
        $userId = Http::requireUserId();
        
        $courses = $this->userModel->getUserCourses($userId);
        $hiddenCourses = $this->hiddenCourseModel->getHiddenCourses($userId);
        $groups = $this->groupModel->getUserGroups($userId);

        View::render('pages/profile', [
            'pageTitle'     => 'Il Mio Profilo - Campusly',
            'activeMenu'    => 'profile',
            'pageCss'       => 'profile',
            'user'          => ['name' => $_SESSION['user_name']],
            'courses'       => $courses,
            'hiddenCourses' => $hiddenCourses,
            'groups'        => $groups 
        ]);
    }

    public function removeCourse(): void
    {
        $userId = Http::requireUserId();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_id'])) {
            $profileId = (int)$_POST['profile_id'];
            $this->userModel->removeUserCourse($userId, $profileId);
            
            header('Location: ' . BASE_PATH . '/profilo?status=removed');
            exit;
        }
    }

    public function syncTheme(): void
    {
        $userId = Http::requireUserId();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Http::json(['error' => 'Metodo non consentito'], 405);
        }
        
        $data = Http::input();
        $theme = $data['theme'] ?? 'system';

        if (!is_string($theme) || !in_array($theme, ['light', 'dark', 'system'], true)) {
            Http::json(['status' => 'error', 'message' => 'Tema non valido'], 400);
        }

        // Il dump rivela che user_preferences ha una chiave unica su user_id, 
        // permettendoci di usare l'ON DUPLICATE KEY UPDATE in modo sicuro
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, theme) VALUES (:uid, :theme)
            ON DUPLICATE KEY UPDATE theme = :theme_update
        ");
        $stmt->execute(['uid' => $userId, 'theme' => $theme, 'theme_update' => $theme]);
        
        $_SESSION['theme'] = $theme;
        Http::json(['status' => 'success']);
    }
}
