<?php
$basePath = defined('BASE_PATH') ? BASE_PATH : '/Campusly';

$url = function (string $path) use ($basePath): string {
    $relativePath = '/public/' . ltrim($path, '/');
    $fullUrl = rtrim($basePath, '/') . $relativePath;
    
    $physicalPath = defined('BASEPATH') ? BASEPATH . $relativePath : $_SERVER['DOCUMENT_ROOT'] . $fullUrl;
    $version = file_exists($physicalPath) ? filemtime($physicalPath) : '1.0';
    
    return htmlspecialchars($fullUrl . '?v=' . $version);
};

$route = function (string $path) use ($basePath): string {
    return htmlspecialchars(rtrim($basePath, '/') . '/' . ltrim($path, '/'));
};

$isLoggedIn = isset($_SESSION['user_id']);
$activePage = $activeMenu ?? ($pageCss ?? 'home');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle ?? 'Campusly') ?></title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="app-version" content="<?= htmlspecialchars(\App\Core\AppVersion::current()) ?>">
    
    <meta name="theme-color" content="#F8F7FA">
    <link rel="manifest" href="<?= $url('/manifest.json') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Campusly">
    
    <link rel="icon" href="<?= $url('/assets/img/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= $url('/assets/img/icon-192.png') ?>">
    
    <link rel="stylesheet" href="<?= $url('/assets/css/style.css') ?>">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= $url('/assets/css/' . $pageCss . '.css') ?>">
    <?php endif; ?>
</head>
<body class="<?= (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark-mode' : '' ?>">

    <header class="site-header">
        <div class="container site-header__inner">
            <a href="<?= $route('/') ?>" class="site-brand">
                <img src="<?= $url('/assets/img/icon-192.png') ?>" alt="Campusly Logo" width="36" height="36" style="border-radius: 8px;">
                <span>Campusly</span>
            </a>

            <nav class="site-nav">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $route('/dashboard') ?>" class="<?= $activePage === 'calendar' ? 'active-link' : '' ?>">Calendario</a>
                    <a href="<?= $route('/profilo') ?>" class="<?= $activePage === 'profile' ? 'active-link' : '' ?>">Profilo</a>
                    <form action="<?= $route('/logout') ?>" method="POST" style="display: inline; margin: 0;" onsubmit="if(navigator.serviceWorker&&navigator.serviceWorker.controller){navigator.serviceWorker.controller.postMessage({type:'CLEAR_USER_DATA'});}">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" style="background: none; border: none; padding: 0; font: inherit; color: #ef4444; font-weight: 600; cursor: pointer;">Esci</button>
                    </form>
                <?php else: ?>
                    <a href="<?= $route('/login') ?>" style="color: var(--text-primary) !important; font-weight: 700; font-size: 1rem;">Accedi</a>
                    <a href="<?= $route('/register') ?>" class="btn btn-primary btn-sm" style="color: #ffffff !important;">Inizia ora</a>
                <?php endif; ?>
            </nav>

        </div>
    </header>
    <main>
