<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

define('BASEPATH', dirname(__DIR__));

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');

if ($basePath === '/public') {
    $basePath = '';
} elseif (str_ends_with($basePath, '/public')) {
    $basePath = substr($basePath, 0, -7);
}

define('BASE_PATH', $basePath);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASEPATH . '/app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

\App\Core\ErrorHandler::register();

$router = new \App\Core\Router();

require BASEPATH . '/config/routes.php';

$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');