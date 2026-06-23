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
        $filtro = $_GET['filtro'] ?? '';
        if (!in_array($filtro, ['', 'ativo', 'inativo'], true)) {
            $filtro = '';
        }

        View::render('superadmin/empresas', [
            'title' => 'Lojas — Superadmin',
            'empresas' => $this->service->listarEmpresas($filtro !== '' ? $filtro : null),
            'filtro' => $filtro,
            'planos' => (new \App\Services\PlanService())->limites(),
        ]);
    }

    public function atualizarEmpresa(): void
    {
        $id = (int) ($_POST['empresa_id'] ?? 0);
        if ($id <= 0) {
            \App\Helpers\Session::flash('error', 'Loja inválida.');
            View::redirect('/superadmin/empresas');
        }

        $this->service->atualizarEmpresa($id, $_POST);
        AuditoriaService::registrar('superadmin_empresa_atualizada', 'empresa', $id, [
            'plano' => $_POST['plano'] ?? null,
            'ativo' => !empty($_POST['ativo']),
            'plano_ativo' => !empty($_POST['plano_ativo']),
        ]);
        \App\Helpers\Session::flash('success', 'Loja atualizada.');
        View::redirect('/superadmin/empresas');
    }

    public function alternarAtivo(): void
    {
        $id = (int) ($_POST['empresa_id'] ?? 0);
        if ($id <= 0) {
            \App\Helpers\Session::flash('error', 'Loja inválida.');
            View::redirect('/superadmin/empresas');
        }

        $ativo = $this->service->alternarAtivo($id);
        AuditoriaService::registrar($ativo ? 'superadmin_loja_ativada' : 'superadmin_loja_desabilitada', 'empresa', $id);
        \App\Helpers\Session::flash('success', $ativo ? 'Loja reativada.' : 'Loja desabilitada.');
        View::redirect('/superadmin/empresas');
    }

    public function alternarPlano(): void
    {
        $id = (int) ($_POST['empresa_id'] ?? 0);
        if ($id <= 0) {
            \App\Helpers\Session::flash('error', 'Loja inválida.');
            View::redirect('/superadmin/empresas');
        }

        $ativo = $this->service->alternarPlanoAtivo($id);
        AuditoriaService::registrar($ativo ? 'superadmin_plano_ativado' : 'superadmin_plano_desativado', 'empresa', $id);
        \App\Helpers\Session::flash('success', $ativo ? 'Plano ativado.' : 'Plano desativado.');
        View::redirect('/superadmin/empresas');
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
