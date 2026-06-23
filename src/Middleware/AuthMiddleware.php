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

        try {
            $stmt = \App\Core\App::pdo()->prepare(
                'SELECT bloqueado, excluido_em FROM usuarios WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => (int) Session::get('usuario_id')]);
            $row = $stmt->fetch();
            if ($row && ((int) ($row['bloqueado'] ?? 0) === 1 || !empty($row['excluido_em']))) {
                (new \App\Services\AuthService())->logout();
                View::redirect('/login');
            }
        } catch (\Throwable) {
            // Coluna bloqueado pode não existir antes da migration 013
        }

        $next();
    }
}
