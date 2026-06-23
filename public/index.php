<?php

declare(strict_types=1);

/**
 * Serve favicon, assets e ícones antes do bootstrap (sem sessão PHP).
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicDir = __DIR__;

$mimeTypes = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    'svg' => 'image/svg+xml',
];

$sendFile = static function (string $path) use ($mimeTypes): void {
    if (!is_file($path)) {
        return;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=604800');
    readfile($path);
    exit;
};

$logo = $publicDir . '/assets/img/logo-rezult.png';

$iconUris = [
    '/favicon.ico',
    '/favicon.png',
    '/favicon-16x16.png',
    '/favicon-32x32.png',
    '/apple-touch-icon.png',
    '/assets/img/favicon.ico',
    '/assets/img/favicon.png',
    '/assets/img/favicon-16x16.png',
    '/assets/img/favicon-32x32.png',
    '/assets/img/apple-touch-icon.png',
];

if (in_array($uri, $iconUris, true)) {
    $basename = basename($uri);
    foreach ([$publicDir . '/' . $basename, $publicDir . '/assets/img/' . $basename] as $candidate) {
        $sendFile($candidate);
    }
    $sendFile($logo);
}

if (str_starts_with($uri, '/assets/')) {
    $base = realpath($publicDir . '/assets');
    $file = realpath($publicDir . $uri);
    if ($base && $file && str_starts_with($file, $base) && is_file($file)) {
        $sendFile($file);
    }
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\ErrorHandler;
use App\Core\Router;

$basePath = dirname(__DIR__);
App::bootstrap($basePath);
ErrorHandler::register();

$router = new Router();
require $basePath . '/routes/web.php';
require $basePath . '/routes/api.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
