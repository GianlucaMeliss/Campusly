<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $app = require BASEPATH . '/config/app.php';

        $defaults = [
            'app' => $app,
            'basePath' => $app['site']['base_path'] ?? '',
            'pageTitle' => $app['seo']['default_title'] ?? $app['site']['name'],
            'pageDescription' => $app['seo']['default_description'] ?? '',
            'pageCss' => null,
        ];

        $data = array_merge($defaults, $data);

        extract($data, EXTR_SKIP);

        $viewFile = BASEPATH . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            exit('View not found: ' . $view);
        }

        require BASEPATH . '/views/layouts/header.php';
        require $viewFile;
        require BASEPATH . '/views/layouts/footer.php';
    }
}