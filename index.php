<?php

declare(strict_types=1);

/**
 * Entrada quando o document root do aaPanel é a raiz do projeto (não public/).
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$root = __DIR__;

$mimeTypes = [
    'ico' => 'image/x-icon',
    'png' => 'image/png',
];

$send = static function (string $path) use ($mimeTypes): void {
    if (!is_file($path)) {
        return;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=604800');
    readfile($path);
    exit;
};

$iconPaths = [
    '/favicon.ico',
    '/favicon.png',
    '/favicon-16x16.png',
    '/favicon-32x32.png',
    '/apple-touch-icon.png',
];

if (in_array($uri, $iconPaths, true)) {
    $name = basename($uri);
    foreach ([
        $root . '/public/' . $name,
        $root . '/public/assets/img/' . $name,
        $root . '/' . $name,
        $root . '/public/assets/img/logo-rezult.png',
    ] as $candidate) {
        $send($candidate);
    }
}

require $root . '/public/index.php';
