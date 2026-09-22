<?php
// Cerchiamo di capire il percorso base corretto. In MAMP di solito è /Campusly
$basePath = defined('BASE_PATH') ? BASE_PATH : '/Campusly';

// Dal tuo albero delle directory, i file sono dentro /public/assets/
$assetBase = $basePath . '/public';
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
    
    <!-- Percorsi Immagini Corretti -->
    <link rel="icon" href="<?= $assetBase ?>/assets/img/icon-192.png">
    <link rel="apple-touch-icon" href="<?= $assetBase ?>/assets/img/icon-192.png">
    
    <!-- Fogli di stile con TIME() per disattivare PERMANENTEMENTE la cache in sviluppo -->
    <link rel="stylesheet" href="<?= $assetBase ?>/assets/css/style.css?v=<?= time() ?>">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= $assetBase ?>/assets/css/<?= $pageCss ?>.css?v=<?= time() ?>">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="<?= $basePath ?>/" class="site-brand">
                <!-- SCUDO DI EMERGENZA: width e height fisici così non esplode mai più -->
                <img src="<?= $assetBase ?>/assets/img/icon-192.png" alt="Campusly Logo" width="36" height="36" style="border-radius: 8px;">
                <span>Campusly</span>
            </a>

            <nav class="site-nav">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $basePath ?>/dashboard" class="<?= $activePage === 'calendar' ? 'active-link' : '' ?>">Calendario</a>
                    <a href="<?= $basePath ?>/profilo" class="<?= $activePage === 'profile' ? 'active-link' : '' ?>">Profilo</a>
                    <a href="<?= $basePath ?>/logout">Esci</a>
                <?php else: ?>
                    <a href="<?= $basePath ?>/login">Accedi</a>
                    <a href="<?= $basePath ?>/register" class="btn btn-primary btn-sm">Inizia ora</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>