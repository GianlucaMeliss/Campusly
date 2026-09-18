<?php
declare(strict_types=1);

return [
    'site' => [
        'name' => 'Project Starter',
        'tagline' => 'Base template for websites and web services',
        'url' => '',
        'locale' => 'it',
        'base_path' => defined('BASE_PATH') ? BASE_PATH : '',
    ],

    'seo' => [
        'default_title' => 'Project Starter',
        'default_description' => 'Template base neutro per progetti web riutilizzabili.',
    ],

    'assets' => [
        'version' => '1.0.0',
    ],
    
    'db' => [
        'host' => 'localhost', // Host del database
        'name' => 'gianlucamelis', // Nome del database
        'user' => 'gianlucamelis', // Username
        'pass' => 'your_password', // Password
        'charset' => 'utf8mb4'
    ],

    'mail' => [
        'contact_email' => 'tuaemail@dominio.it', // Cambia questa email per ogni nuovo progetto
        'contact_subject' => '🔔 Nuovo Contatto dal Sito',
    ],
];