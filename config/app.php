<?php
declare(strict_types=1);

return [
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

    'assets' => [
        'version' => '1.0.0',
    ],

    'db' => [
        'host' => 'localhost',
        'name' => 'my_gianlucamelis',
        'user' => 'gianlucamelis',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'from' => 'noreply@campusly.it',
        'contact_email' => 'melis.gianlucagm@gmail.com',
        'contact_subject' => '🔔 Nuovo Contatto dal Sito',
    ],
];
