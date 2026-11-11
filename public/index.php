<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
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

// Verifica validità sessione rispetto a cambi password (al massimo una volta ogni 60 secondi)
if (!empty($_SESSION['user_id'])) {
    $lastPwCheck = (int)($_SESSION['pw_check_at'] ?? 0);
    if ((time() - $lastPwCheck) >= 60) {
        $_SESSION['pw_check_at'] = time();
        $userModel = new \App\Models\UserModel();
        $dbChangedAt = $userModel->getPasswordChangedAt((int)$_SESSION['user_id']);
        $sessChangedAt = $_SESSION['password_changed_at'] ?? null;

        if ($dbChangedAt !== null && ($sessChangedAt === null || strtotime($dbChangedAt) > strtotime((string)$sessChangedAt))) {
            $_SESSION = [];
            session_destroy();
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
    }
}

// Ripristino automatico della sessione tramite "Remember Me" con rotazione del token
if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_me']) && is_string($_COOKIE['remember_me'])) {
    $oldTokenHash = hash('sha256', $_COOKIE['remember_me']);
    $userModel = new \App\Models\UserModel();
    $user = $userModel->findUserByToken($oldTokenHash);

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'];
        $_SESSION['password_changed_at'] = $user['password_changed_at'] ?? null;
        $_SESSION['pw_check_at'] = time();

        $userModel->deleteToken($oldTokenHash);
        $newToken = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newToken);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + (365 * 24 * 60 * 60));
        $userModel->storeRememberToken((int)$user['id'], $newTokenHash, $expiresAt);

        setcookie('remember_me', $newToken, [
            'expires'  => time() + (365 * 24 * 60 * 60),
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT theme FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $_SESSION['theme'] = $stmt->fetchColumn() ?: 'light';
    } else {
        setcookie('remember_me', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

$router = new \App\Core\Router();

require BASEPATH . '/config/routes.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    \App\Core\Csrf::verifyOrFail();
}

$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');
