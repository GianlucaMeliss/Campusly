<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\PageController;

/** @var Router $router */

// Pagine Statiche Principali
$router->get('/', function (): void {
    (new HomeController())->index();
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