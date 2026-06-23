<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\ErrorHandler;
use App\Core\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);
App::bootstrap($basePath);
ErrorHandler::register();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Favicon e ícones na raiz de public/
$rootStatic = [
    '/favicon.ico' => 'image/x-icon',
    '/favicon.png' => 'image/png',
    '/favicon-16x16.png' => 'image/png',
    '/favicon-32x32.png' => 'image/png',
    '/apple-touch-icon.png' => 'image/png',
];
if (isset($rootStatic[$uri])) {
    $file = __DIR__ . $uri;
    if (is_file($file)) {
        header('Content-Type: ' . $rootStatic[$uri]);
        header('Cache-Control: public, max-age=604800');
        readfile($file);
        exit;
    }
}

// Assets estáticos públicos
if (str_starts_with($uri, '/assets/')) {
    $base = realpath(__DIR__ . '/assets');
    $file = realpath(__DIR__ . $uri);
    if ($base && $file && str_starts_with($file, $base) && is_file($file)) {
        $types = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'];
        header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

$router = new Router();
require $basePath . '/routes/web.php';
require $basePath . '/routes/api.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
