<?php
declare(strict_types=1);

$config = [
    'site' => [
        'name' => 'Campusly',
        'tagline' => 'Il tuo calendario universitario',
        'url' => 'https://gianlucamelis.altervista.org/Campusly',
        'locale' => 'it',
        'base_path' => defined('BASE_PATH') ? BASE_PATH : '',
    ],

    'seo' => [
        'default_title' => 'Campusly',
        'default_description' => 'Il tuo calendario universitario per non perdere mai più un evento accademico.',
    ],

    'db' => [
        'host' => 'localhost',
        'name' => '',
        'user' => '',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'from' => 'noreply@example.com',
        'contact_email' => 'info@example.com',
        'contact_subject' => '🔔 Nuovo Contatto dal Sito',
    ],

    'security' => [
        'trusted_proxies' => [],
    ],
];

$localFile = __DIR__ . '/local.php';
if (is_file($localFile)) {
    $localConfig = require $localFile;
    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    }
}

return $config;
