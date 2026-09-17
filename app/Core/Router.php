<?php
declare(strict_types=1);

namespace App\Core;

/**
 * * Questo router mappa in modo diretto e lineare una stringa URL a un metodo Controller.
 * È progettato per essere estremamente veloce e leggibile.
 * * COSA NON FA E NON DEVE FARE:
 * - Niente Middleware
 * - Niente Grouping delle rotte
 * - Niente Naming delle rotte
 * * Se il progetto che stai sviluppando inizia a richiedere queste funzionalità 
 * (es. aree riservate complesse, multi-tenant), questo boilerplate NON È PIÙ 
 * LO STRUMENTO GIUSTO. Fermati e passa a un framework completo.
 */
final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = $this->extractPath($uri);
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route => $handler) {
            $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([a-zA-Z0-9\-]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                call_user_func_array($handler, $matches);
                return;
            }
        }

        http_response_code(404);

        if (class_exists(\App\Controllers\PageController::class)) {
            (new \App\Controllers\PageController())->notFound();
            return;
        }

        echo '404 - Page not found';
    }

    private function extractPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = '/' . ltrim($path, '/');

        if (defined('BASE_PATH') && BASE_PATH !== '') {
            if ($path === BASE_PATH) {
                $path = '/';
            } elseif (strpos($path, BASE_PATH . '/') === 0) {
                $path = substr($path, strlen(BASE_PATH));
            }
        }

        return $this->normalize($path);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }
}