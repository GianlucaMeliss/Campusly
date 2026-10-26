<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;

/** @var Router $router */

// Rotte Pubbliche Auth
$router->get('/login', function (): void { (new AuthController())->showLogin(); });
$router->post('/login', function (): void { (new AuthController())->processLogin(); });
$router->get('/register', function (): void { (new AuthController())->showRegister(); });
$router->post('/register', function (): void { (new AuthController())->processRegister(); });
$router->get('/logout', function (): void { (new AuthController())->logout(); });
$router->get('/api/eventi-personali', function (): void { (new ApiController())->getPersonalEvents(); });
$router->post('/api/eventi-personali', function (): void { (new ApiController())->savePersonalEvent(); });
$router->post('/api/eventi-personali/delete/{id}', function (string $id): void { (new ApiController())->deletePersonalEvent($id); });
$router->get('/api/corsi-nascosti', function (): void { (new ApiController())->getHiddenCourses(); });
$router->post('/api/corsi-nascosti/toggle', function (): void { (new ApiController())->toggleHiddenCourse(); });
$router->get('/api/calendario', function (): void { (new ApiController())->getCalendarEvents(); });
$router->get('/api/versione', function (): void { (new ApiController())->getAppVersion(); });
$router->post('/api/richiesta-onboarding', function () { (new \App\Controllers\ApiController())->submitOnboardingRequest(); });
$router->post('/api/gruppi/crea', function (): void { (new ApiController())->createGroup(); });
$router->post('/api/gruppi/unisciti', function (): void { (new ApiController())->joinGroup(); });
$router->post('/api/gruppi/lascia', function (): void { (new ApiController())->leaveGroup(); });
$router->post('/api/gruppi/privacy', function (): void { (new ApiController())->updateGroupPrivacy(); });
$router->get('/api/calendario/gruppo/{id}', function (string $id): void { (new ApiController())->getGroupCalendar($id); });

// Middleware/Controllo di Sicurezza (Funzione helper)
$requireAuth = function() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }
};

$router->get('/dashboard', function () use ($requireAuth): void {
    $requireAuth(); // Verifica che sia loggato
    
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
        'pageCss' => 'calendar',
        'course' => $config,
        'groups' => $userGroups
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
// Nuova rotta API per recuperare i corsi di una specifica università
$router->get('/api/universita/{id}/corsi', function (string $id): void { (new ApiController())->getCoursesByUniversity($id); });
$router->get('/api/corsi/{id}/curriculums', function (string $id): void { (new ApiController())->getCourseCurriculums($id); });
$router->post('/profilo/rimuovi-corso', function () use ($requireAuth): void { $requireAuth(); (new ProfileController())->removeCourse(); });

// Pagine Profilo
$router->get('/profilo', function () use ($requireAuth): void {
    $requireAuth();
    (new ProfileController())->showProfile();
});
// API Sincronizzazione Tema
$router->post('/api/preferenze/tema', function () use ($requireAuth): void {
    $requireAuth();
    (new ProfileController())->syncTheme();
});

// Pagine Statiche Principali
$router->get('/', function (): void {
    (new HomeController())->index();
});

$router->get('/', function (): void {
    \App\Core\View::render('pages/home', [
        'pageTitle' => 'Campusly - Il tuo Calendario Universitario',
        'pageCss' => 'home' 
    ]);
});

$router->get('/chi-siamo', function (): void {
    (new PageController())->about();
});

// Pagina archivio generale
$router->get('/servizi', function (): void {
    (new PageController())->archive();
});

// Pagina di dettaglio dinamica (legge lo {slug} dall'URL)
$router->get('/servizi/{slug}', function (string $slug): void {
    (new PageController())->showItem($slug);
});