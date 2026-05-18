<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
    /** @var array<string, array<int, array{pattern: string, handler: callable, middleware: array}>> */
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable $handler, array $middleware): void
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                fn ($k) => is_string($k),
                ARRAY_FILTER_USE_KEY
            );
            $params = array_map(
                static fn ($v) => is_string($v) && ctype_digit($v) ? (int) $v : $v,
                $params
            );

            $handler = $route['handler'];
            $pipeline = array_reduce(
                array_reverse($route['middleware']),
                fn ($next, $mw) => fn () => $mw($next),
                fn () => $handler(...array_values($params))
            );

            $pipeline();
            return;
        }

        http_response_code(404);
        $layout = isset($_SESSION['usuario_id']) ? 'app' : 'guest';
        View::render('errors/404', ['title' => 'Página não encontrada'], layout: $layout);
    }
}
