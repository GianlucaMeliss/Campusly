<?php
declare(strict_types=1);

$baseConfig = [
    'site' => [
        'name' => 'Campusly',
        'tagline' => 'Il tuo calendario universitario',
        'url' => '',
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
        'from' => 'noreply@campusly.it',
        'contact_email' => 'info@campusly.it',
        'contact_subject' => '🔔 Nuovo Contatto dal Sito',
    ],
];

$localConfigFile = __DIR__ . '/local.php';
if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;
    if (is_array($localConfig)) {
        $baseConfig = array_replace_recursive($baseConfig, $localConfig);
    }
}

return $baseConfig;
