<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ApiController;
use App\Controllers\CalendarApiController;
use App\Controllers\GroupApiController;
use App\Controllers\OnboardingApiController;
use App\Controllers\ContactController;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;

/** @var Router $router */

// Rotte Pubbliche Auth & Recupero Password
$router->get('/login', function (): void { (new AuthController())->showLogin(); });
$router->post('/login', function (): void { (new AuthController())->processLogin(); });
$router->get('/register', function (): void { (new AuthController())->showRegister(); });
$router->post('/register', function (): void { (new AuthController())->processRegister(); });
$router->post('/logout', function (): void { (new AuthController())->logout(); });

$router->get('/password-dimenticata', function (): void { (new AuthController())->showForgotPassword(); });
$router->post('/password-dimenticata', function (): void { (new AuthController())->processForgotPassword(); });
$router->get('/reset-password/{token}', function (string $token): void { (new AuthController())->showResetPassword($token); });
$router->post('/reset-password/{token}', function (string $token): void { (new AuthController())->processResetPassword($token); });

// API Calendario, Eventi Personali e Materie Nascoste
$router->get('/api/calendario', function (): void { (new CalendarApiController())->getCalendarEvents(); });
$router->get('/api/eventi-personali', function (): void { (new CalendarApiController())->getPersonalEvents(); });
$router->post('/api/eventi-personali', function (): void { (new CalendarApiController())->savePersonalEvent(); });
$router->post('/api/eventi-personali/delete/{id}', function (string $id): void { (new CalendarApiController())->deletePersonalEvent($id); });
$router->get('/api/corsi-nascosti', function (): void { (new CalendarApiController())->getHiddenCourses(); });
$router->post('/api/corsi-nascosti/toggle', function (): void { (new CalendarApiController())->toggleHiddenCourse(); });

// API Gruppi di Studio
$router->post('/api/gruppi/crea', function (): void { (new GroupApiController())->createGroup(); });
$router->post('/api/gruppi/unisciti', function (): void { (new GroupApiController())->joinGroup(); });
$router->post('/api/gruppi/lascia', function (): void { (new GroupApiController())->leaveGroup(); });
$router->post('/api/gruppi/privacy', function (): void { (new GroupApiController())->updateGroupPrivacy(); });
$router->get('/api/calendario/gruppo/{id}', function (string $id): void { (new GroupApiController())->getGroupCalendar($id); });

// API Onboarding, Versione e Contatti
$router->get('/api/universita/{id}/corsi', function (string $id): void { (new OnboardingApiController())->getCoursesByUniversity($id); });
$router->get('/api/corsi/{id}/curriculums', function (string $id): void { (new OnboardingApiController())->getCourseCurriculums($id); });
$router->post('/api/richiesta-onboarding', function (): void { (new OnboardingApiController())->submitOnboardingRequest(); });
$router->get('/api/versione', function (): void { (new ApiController())->getAppVersion(); });
$router->post('/api/log-cookie', function (): void { (new ContactController())->logCookie(); });
$router->post('/api/contact', function (): void { (new ContactController())->sendContact(); });

// Controllo di autenticazione per le pagine protette
$requireAuth = function (): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }
};

$router->get('/dashboard', function () use ($requireAuth): void {
    $requireAuth();
    
    $userId = (int)$_SESSION['user_id'];
    $userModel = new \App\Models\UserModel();
    $config = $userModel->getUserCourseConfig($userId);
    
    if (!$config) {
        header('Location: ' . BASE_PATH . '/onboarding');
        exit;
    }

    $groupModel = new \App\Models\GroupModel();
    $userGroups = $groupModel->getUserGroups($userId);

    \App\Core\View::render('pages/calendar', [
        'pageTitle' => 'Il Mio Calendario',
        'pageCss'   => 'calendar',
        'course'    => $config,
        'groups'    => $userGroups
    ]);
});

$router->get('/onboarding', function () use ($requireAuth): void {
    $requireAuth();
    (new AuthController())->showOnboarding();
});
$router->post('/onboarding', function () use ($requireAuth): void {
    $requireAuth();
    (new AuthController())->processOnboarding();
});

// Pagine Profilo
$router->get('/profilo', function () use ($requireAuth): void {
    $requireAuth();
    (new ProfileController())->showProfile();
});
$router->post('/profilo/rimuovi-corso', function () use ($requireAuth): void {
    $requireAuth();
    (new ProfileController())->removeCourse();
});
$router->post('/api/preferenze/tema', function () use ($requireAuth): void {
    $requireAuth();
    (new ProfileController())->syncTheme();
});

// Pagine Pubbliche Principali (rimossa rotta '/' duplicata)
$router->get('/', function (): void {
    (new HomeController())->index();
});

$router->get('/chi-siamo', function (): void {
    (new PageController())->about();
});

$router->get('/servizi', function (): void {
    (new PageController())->archive();
});

$router->get('/servizi/{slug}', function (string $slug): void {
    (new PageController())->showItem($slug);
});

// Rotta Magic Link Invito Gruppo
$router->get('/invito/{code}', function (string $code): void {
    $cleanCode = strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $code));

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['pending_invite_code'] = $cleanCode;
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    $groupModel = new \App\Models\GroupModel();
    $group = $groupModel->getGroupByCode($cleanCode);

    \App\Core\View::render('pages/invite', [
        'pageTitle' => $group ? 'Invito: ' . $group['name'] : 'Invito Gruppo - Campusly',
        'group'     => $group
    ]);
});
