<?php
// Carica la configurazione e calcola il percorso base dinamico
$app = $app ?? require BASEPATH . '/config/app.php';
$basePath = rtrim($app['site']['base_path'] ?? '', '/');
$url = static function (string $path = '/') use ($basePath): string {
    return $basePath . '/' . ltrim($path, '/');
};

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle ?? 'Campusly') ?></title>
    
    <!-- PWA e Colori di Sistema aggiornati all'identità Campusly -->
    <meta name="theme-color" content="#F8F7FA">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Percorsi dinamici per loghi e manifest -->
    <link rel="icon" href="<?= htmlspecialchars($url('/assets/img/icon-192.png')) ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($url('/assets/img/icon-192.png')) ?>">
    <link rel="manifest" href="<?= htmlspecialchars($url('/assets/manifest.json')) ?>">
    
    <!-- Fogli di stile -->
    <link rel="stylesheet" href="<?= htmlspecialchars($url('/assets/css/style.css')) ?>?v=3">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($url('/assets/css/' . $pageCss . '.css')) ?>?v=3">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="<?= htmlspecialchars($url('/')) ?>" class="site-brand">
                <img src="<?= htmlspecialchars($url('/assets/img/icon-192.png')) ?>" alt="Campusly Logo">
                <span>Campusly</span>
            </a>

            <nav class="site-nav">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= htmlspecialchars($url('/dashboard')) ?>">Calendario</a>
                    <a href="<?= htmlspecialchars($url('/profilo')) ?>">Profilo</a>
                    <a href="<?= htmlspecialchars($url('/logout')) ?>">Esci</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url('/login')) ?>">Accedi</a>
                    <a href="<?= htmlspecialchars($url('/register')) ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">Inizia ora</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>