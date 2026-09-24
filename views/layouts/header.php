<?php
// Gestione percorsi sicura per l'ambiente di produzione
$basePath = defined('BASE_PATH') ? BASE_PATH : '/Campusly';

/**
 * 1. HELPER PER GLI ASSETS (CSS, JS, Immagini)
 * Aggiunge /public/ e il versioning automatico contro la cache del browser.
 * Manteniamo il nome $url per compatibilità con le viste (es. calendar.php)
 */
$url = function (string $path) use ($basePath): string {
    $relativePath = '/public/' . ltrim($path, '/');
    $fullUrl = rtrim($basePath, '/') . $relativePath;
    
    // Cache busting automatico basato sull'ultima modifica del file
    $physicalPath = defined('BASEPATH') ? BASEPATH . $relativePath : $_SERVER['DOCUMENT_ROOT'] . $fullUrl;
    $version = file_exists($physicalPath) ? filemtime($physicalPath) : '1.0';
    
    return htmlspecialchars($fullUrl . '?v=' . $version);
};

/**
 * 2. HELPER PER LE ROTTE (Pagine, Login, Dashboard)
 * Crea URL puliti senza /public/ e senza ?v=...
 */
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
    
    <meta name="theme-color" content="#F8F7FA">
    <link rel="manifest" href="<?= $url('/manifest.json') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Campusly">
    
    <!-- USA IL RESOURCE HELPER ($url) PER IMMAGINI E CSS -->
    <link rel="icon" href="<?= $url('/assets/img/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= $url('/assets/img/icon-192.png') ?>">
    
    <link rel="stylesheet" href="<?= $url('/assets/css/style.css') ?>">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= $url('/assets/css/' . $pageCss . '.css') ?>">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <!-- USA IL ROUTE HELPER ($route) PER I COLLEGAMENTI ALLE PAGINE -->
            <a href="<?= $route('/') ?>" class="site-brand">
                <img src="<?= $url('/assets/img/icon-192.png') ?>" alt="Campusly Logo" width="36" height="36" style="border-radius: 8px;">
                <span>Campusly</span>
            </a>

            <nav class="site-nav">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $route('/dashboard') ?>" class="<?= $activePage === 'calendar' ? 'active-link' : '' ?>">Calendario</a>
                    <a href="<?= $route('/profilo') ?>" class="<?= $activePage === 'profile' ? 'active-link' : '' ?>">Profilo</a>
                    <a href="<?= $route('/logout') ?>">Esci</a>
                <?php else: ?>
                    <a href="<?= $route('/login') ?>">Accedi</a>
                    <a href="<?= $route('/register') ?>" class="btn btn-primary btn-sm">Inizia ora</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>