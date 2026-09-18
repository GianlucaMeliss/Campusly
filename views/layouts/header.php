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
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="<?= htmlspecialchars($url('/')) ?>" class="site-brand">
                <span class="site-brand__name"><?= htmlspecialchars($siteName) ?></span>
                <?php if (!empty($siteTagline)): ?>
                    <span class="site-brand__tagline"><?= htmlspecialchars($siteTagline) ?></span>
                <?php endif; ?>
            </a>

            <nav class="site-nav" aria-label="Navigazione principale">
                <a href="<?= htmlspecialchars($url('/')) ?>">Home</a>
                <a href="<?= htmlspecialchars($url('/chi-siamo')) ?>">Chi siamo</a>
            </nav>
        </div>
    </header>

    <main class="site-main">