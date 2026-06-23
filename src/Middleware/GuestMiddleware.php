<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Session;

final class GuestMiddleware
{
    public function __invoke(callable $next): void
    {
        if (Session::get('usuario_id')) {
            View::redirect((new \App\Services\AuthService())->rotaPosLogin());
        }
        $next();
    }
}
