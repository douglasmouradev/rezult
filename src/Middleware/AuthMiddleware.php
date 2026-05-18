<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Session;

final class AuthMiddleware
{
    public function __invoke(callable $next): void
    {
        if (!Session::get('usuario_id')) {
            if (!empty($_COOKIE['remember'])) {
                try {
                    (new \App\Services\AuthService())->tentarRememberLogin();
                } catch (\PDOException) {
                    // Banco indisponível — segue para login
                }
            }
        }
        if (!Session::get('usuario_id')) {
            View::redirect('/login');
        }
        $next();
    }
}
