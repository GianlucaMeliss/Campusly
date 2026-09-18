<?php
$app = $app ?? require BASEPATH . '/config/app.php';
$siteName = $app['site']['name'] ?? 'Project Starter';
$siteTagline = $app['site']['tagline'] ?? '';
$locale = $app['site']['locale'] ?? 'it';
$assetsVersion = $app['assets']['version'] ?? '1.0.0';
$basePath = rtrim($app['site']['base_path'] ?? '', '/');

$url = static function (string $path = '/') use ($basePath): string {
    $path = '/' . ltrim($path, '/');
    return ($basePath !== '' ? $basePath : '') . $path;
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>
    <!-- PWA e Mobile settings -->
    <link rel="manifest" href="<?= htmlspecialchars($url('/manifest.json')) ?>">
    <meta name="theme-color" content="#007161">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Campusly">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($url('/img/icon-192.png')) ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $siteName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? '') ?>">

    <link rel="icon" href="<?= htmlspecialchars($url('/favicon.ico')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('/assets/css/style.css')) ?>?v=<?= urlencode($assetsVersion) ?>">

    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($url('/assets/css/' . $pageCss . '.css')) ?>?v=<?= urlencode($assetsVersion) ?>">
    <?php endif; ?>
</head>
<body>
    <header class="site-header" style="background: var(--surface); box-shadow: var(--shadow-soft);">
        <div class="container site-header__inner" style="display: flex; justify-content: space-between; align-items: center; padding: 15px;">
            <a href="<?= htmlspecialchars($url('/')) ?>" class="site-brand" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <img src="<?= htmlspecialchars($url('/img/icon-192.png')) ?>" alt="Logo" style="height: 32px; border-radius: 6px;">
                <span style="font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">Campusly</span>
            </a>

            <nav class="site-nav" aria-label="Navigazione principale" style="display: flex; gap: 15px;">
                <a href="<?= htmlspecialchars($url('/')) ?>" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">Home</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= htmlspecialchars($url('/dashboard')) ?>" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">Calendario</a>
                    <a href="<?= htmlspecialchars($url('/logout')) ?>" style="color: #ef4444; text-decoration: none; font-weight: 500;">Esci</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url('/login')) ?>" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Accedi</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="site-main">