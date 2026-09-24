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
$router->get('/api/eventi-personali/delete/{id}', function (string $id): void { (new ApiController())->deletePersonalEvent($id); });
$router->get('/api/corsi-nascosti', function (): void { (new ApiController())->getHiddenCourses(); });
$router->post('/api/corsi-nascosti/toggle', function (): void { (new ApiController())->toggleHiddenCourse(); });
$router->get('/api/calendario', function (): void { (new ApiController())->getCalendarEvents(); });

// Middleware/Controllo di Sicurezza (Funzione helper)
$requireAuth = function() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }
};

$router->get('/dashboard', function () use ($requireAuth): void {
    $requireAuth(); // Verifica che sia loggato
    
    // Verifica che abbia completato l'onboarding
    $userModel = new \App\Models\UserModel();
    $config = $userModel->getUserCourseConfig((int)$_SESSION['user_id']);
    
    if (!$config) {
        header('Location: ' . BASE_PATH . '/onboarding');
        exit;
    }

    \App\Core\View::render('pages/calendar', [
        'pageTitle' => 'Il Mio Calendario',
        'pageCss' => 'calendar',
        'course' => $config
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
$router->post('/profilo/aggiorna-corso', function () use ($requireAuth): void {
    $requireAuth();
    (new ProfileController())->updateCourse();
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

// Esempio di rotta dinamica per gestire futuri elementi (es. servizi, portfolio)
// $router->get('/item/{slug}', function (string $slug): void {
//     (new PageController())->showItem($slug);
// });