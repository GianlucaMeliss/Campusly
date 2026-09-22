<?php
// Usiamo la costante globale sicura
$basePath = defined('BASE_PATH') ? BASE_PATH : '';

// Helper infallibile: forza il percorso a passare per la cartella /public/
$url = function (string $path = '/') use ($basePath): string {
    return rtrim($basePath, '/') . '/public/' . ltrim($path, '/');
};

$isLoggedIn = isset($_SESSION['user_id']);
$activePage = $pageCss ?? 'home'; 
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle ?? 'Campusly') ?></title>
    
    <meta name="theme-color" content="#F8F7FA">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Percorsi Immagini Corretti -->
    <link rel="icon" href="<?= htmlspecialchars($url('/assets/img/icon-192.png')) ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($url('/assets/img/icon-192.png')) ?>">
    
    <!-- Fogli di stile puntati correttamente a /public/assets/css/... -->
    <link rel="stylesheet" href="<?= htmlspecialchars($url('/assets/css/style.css')) ?>?v=10">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($url('/assets/css/' . $pageCss . '.css')) ?>?v=10">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="<?= htmlspecialchars($basePath . '/') ?>" class="site-brand">
                <img src="<?= htmlspecialchars($url('/assets/img/icon-192.png')) ?>" alt="Campusly Logo">
                <span>Campusly</span>
            </a>

            <nav class="site-nav">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= htmlspecialchars($basePath . '/dashboard') ?>" class="<?= $activePage === 'calendar' ? 'active-link' : '' ?>">Calendario</a>
                    <a href="<?= htmlspecialchars($basePath . '/profilo') ?>" class="<?= $activePage === 'profile' ? 'active-link' : '' ?>">Profilo</a>
                    <a href="<?= htmlspecialchars($basePath . '/logout') ?>">Esci</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($basePath . '/login') ?>">Accedi</a>
                    <a href="<?= htmlspecialchars($basePath . '/register') ?>" class="btn btn-primary btn-sm">Inizia ora</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>