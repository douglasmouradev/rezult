<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\App;
use App\Core\View;
use App\Helpers\Session;

final class SuperAdminPolicy
{
    public static function isSuperadmin(?int $usuarioId = null): bool
    {
        $uid = $usuarioId ?? (int) Session::get('usuario_id');
        if ($uid <= 0) {
            return false;
        }

        if (isset($_SESSION['is_superadmin'])) {
            return (bool) $_SESSION['is_superadmin'];
        }

        try {
            $stmt = App::pdo()->prepare('SELECT is_superadmin FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $uid]);
            $flag = (int) $stmt->fetchColumn() === 1;
        } catch (\Throwable) {
            return false;
        }
        Session::set('is_superadmin', $flag);

        return $flag;
    }

    public static function abortUnlessSuperadmin(): void
    {
        if (!self::isSuperadmin()) {
            Session::flash('error', 'Acesso restrito a superadministradores.');
            View::redirect('/dashboard');
        }
    }
}
