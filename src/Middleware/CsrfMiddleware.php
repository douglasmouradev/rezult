<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Session;

final class CsrfMiddleware
{
    public function __invoke(callable $next): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!Csrf::validate($token)) {
                Session::flash('error', 'Token de segurança inválido. Tente novamente.');
                View::redirect(self::safeRedirectPath());
            }
        }
        $next();
    }

    private static function safeRedirectPath(): string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref === '') {
            return '/';
        }
        $path = parse_url($ref, PHP_URL_PATH);
        return is_string($path) && str_starts_with($path, '/') ? $path : '/';
    }
}
