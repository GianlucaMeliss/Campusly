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
        'host' => 'localhost', // Host del database
        'name' => 'my_gianlucamelis', // Nome del database
        'user' => 'gianlucamelis', // Username
        'pass' => '', // Password
        'charset' => 'utf8mb4'
    ],

    'mail' => [
        'contact_email' => 'melis.gianlucagm@gmail.com', // Cambia questa email per ogni nuovo progetto
        'contact_subject' => '🔔 Nuovo Contatto dal Sito',
    ],
];