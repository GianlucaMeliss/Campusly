<?php
// Gestione percorsi sicura per l'ambiente di produzione
$basePath = defined('BASE_PATH') ? BASE_PATH : '';

// Helper di produzione: aggancia /public/ e aggiunge il versioning automatico basato sul file
$assetUrl = function (string $path) use ($basePath): string {
    $relativePath = '/public/' . ltrim($path, '/');
    $fullUrl = rtrim($basePath, '/') . $relativePath;
    
    // Cache busting intelligente: usa la data di modifica del file
    $physicalPath = BASEPATH . $relativePath;
    $version = file_exists($physicalPath) ? filemtime($physicalPath) : '1.0';
    
    return htmlspecialchars($fullUrl . '?v=' . $version);
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
    
    <link rel="icon" href="<?= $assetUrl('/assets/img/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= $assetUrl('/assets/img/icon-192.png') ?>">
    
    <!-- CSS Generali e Specifici con cache-busting automatico -->
    <link rel="stylesheet" href="<?= $assetUrl('/assets/css/style.css') ?>">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= $assetUrl('/assets/css/' . $pageCss . '.css') ?>">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="<?= htmlspecialchars($basePath . '/') ?>" class="site-brand">
                <img src="<?= $assetUrl('/assets/img/icon-192.png') ?>" alt="Campusly Logo" width="36" height="36" style="border-radius: 8px;">
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