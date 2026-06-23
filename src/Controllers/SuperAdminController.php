<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuditoriaService;
use App\Services\SuperAdminService;

final class SuperAdminController
{
    public function __construct(
        private SuperAdminService $service = new SuperAdminService(),
    ) {}

    public function index(): void
    {
        View::render('superadmin/index', [
            'title' => 'Superadmin',
            'stats' => $this->service->dashboard(),
            'recentes' => $this->service->usuariosRecentes(),
        ]);
    }

    public function usuarios(): void
    {
        View::render('superadmin/usuarios', [
            'title' => 'Usuários — Superadmin',
            'usuarios' => $this->service->listarUsuarios(),
        ]);
    }

    public function empresas(): void
    {
        View::render('superadmin/empresas', [
            'title' => 'Empresas — Superadmin',
            'empresas' => $this->service->listarEmpresas(),
        ]);
    }

    public function logins(): void
    {
        View::render('superadmin/logins', [
            'title' => 'Logins — Superadmin',
            'logins' => $this->service->listarLogins(),
        ]);
    }

    public function promover(): void
    {
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            \App\Helpers\Session::flash('error', 'Usuário inválido.');
            View::redirect('/superadmin/usuarios');
        }

        \App\Core\App::pdo()->prepare('UPDATE usuarios SET is_superadmin = 1 WHERE id = :id')
            ->execute(['id' => $usuarioId]);

        AuditoriaService::registrar('superadmin_promovido', 'usuario', $usuarioId);
        \App\Helpers\Session::flash('success', 'Usuário promovido a superadmin.');
        View::redirect('/superadmin/usuarios');
    }

    public function revogar(): void
    {
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $meuId = (int) \App\Helpers\Session::get('usuario_id');

        if ($usuarioId <= 0 || $usuarioId === $meuId) {
            \App\Helpers\Session::flash('error', 'Não é possível revogar este usuário.');
            View::redirect('/superadmin/usuarios');
        }

        \App\Core\App::pdo()->prepare('UPDATE usuarios SET is_superadmin = 0 WHERE id = :id')
            ->execute(['id' => $usuarioId]);

        AuditoriaService::registrar('superadmin_revogado', 'usuario', $usuarioId);
        \App\Helpers\Session::flash('success', 'Superadmin revogado.');
        View::redirect('/superadmin/usuarios');
    }
}
