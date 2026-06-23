<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\Validator;
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
            'loginsPorDia' => $this->service->loginsPorDia(14),
            'expirando' => $this->service->empresasPlanoExpirando(7),
        ]);
    }

    public function usuarios(): void
    {
        $filtro = $_GET['filtro'] ?? '';
        if (!in_array($filtro, ['', 'ativos', 'bloqueados', 'excluidos'], true)) {
            $filtro = '';
        }

        View::render('superadmin/usuarios', [
            'title' => 'Usuários — Superadmin',
            'usuarios' => $this->service->listarUsuarios($filtro !== '' ? $filtro : null),
            'filtro' => $filtro,
        ]);
    }

    public function usuarioCriarForm(): void
    {
        View::render('superadmin/usuario-form', [
            'title' => 'Novo usuário — Superadmin',
            'usuario' => null,
        ]);
    }

    public function usuarioCriar(): void
    {
        $v = new Validator($_POST);
        $v->required('nome', 'email', 'senha', 'senha_confirmacao')->email('email')->password('senha');
        if (($_POST['senha'] ?? '') !== ($_POST['senha_confirmacao'] ?? '')) {
            Session::flash('error', 'As senhas não coincidem.');
            View::redirect('/superadmin/usuarios/criar');
        }
        if ($v->fails()) {
            Session::flash('error', $v->first());
            View::redirect('/superadmin/usuarios/criar');
        }

        try {
            $id = $this->service->criarUsuario(
                Sanitize::raw($_POST['nome']),
                Sanitize::raw($_POST['email']),
                $_POST['senha'],
                !empty($_POST['email_verificado']),
                !empty($_POST['is_superadmin']),
            );
            AuditoriaService::registrar('superadmin_usuario_criado', 'usuario', $id);
            Session::flash('success', 'Usuário criado.');
            View::redirect('/superadmin/usuarios/' . $id);
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            View::redirect('/superadmin/usuarios/criar');
        }
    }

    public function usuarioVer(int $id): void
    {
        try {
            $usuario = $this->service->buscarUsuario($id);
            if (!$usuario) {
                Session::flash('error', 'Usuário não encontrado.');
                View::redirect('/superadmin/usuarios');
            }

            $empresas = [];
            $logins = [];
            try {
                $empresas = $this->service->empresasDoUsuario($id);
            } catch (\Throwable) {
                $empresas = [];
            }
            try {
                $logins = $this->service->loginsDoUsuario((string) ($usuario['email'] ?? ''));
            } catch (\Throwable) {
                $logins = [];
            }

            View::render('superadmin/usuario', [
                'title' => 'Usuário — Superadmin',
                'usuario' => $usuario,
                'empresas' => $empresas,
                'logins' => $logins,
            ]);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('superadmin usuarioVer: ' . $e->getMessage(), ['id' => $id]);
            Session::flash('error', 'Erro ao carregar usuário. Execute php bin/migrate.php no servidor.');
            View::redirect('/superadmin/usuarios');
        }
    }

    public function usuarioAtualizar(int $id): void
    {
        $meuId = (int) Session::get('usuario_id');
        if ($id === $meuId && empty($_POST['is_superadmin'])) {
            Session::flash('error', 'Você não pode remover seu próprio superadmin.');
            View::redirect('/superadmin/usuarios/' . $id);
        }

        try {
            if (!$this->service->atualizarUsuario($id, $_POST)) {
                Session::flash('error', 'Usuário não encontrado ou excluído.');
                View::redirect('/superadmin/usuarios');
            }
            AuditoriaService::registrar('superadmin_usuario_atualizado', 'usuario', $id);
            Session::flash('success', 'Usuário atualizado.');
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }

        View::redirect('/superadmin/usuarios/' . $id);
    }

    public function usuarioSenha(int $id): void
    {
        $v = new Validator($_POST);
        $v->required('senha', 'senha_confirmacao')->password('senha');
        if (($_POST['senha'] ?? '') !== ($_POST['senha_confirmacao'] ?? '')) {
            Session::flash('error', 'As senhas não coincidem.');
            View::redirect('/superadmin/usuarios/' . $id);
        }
        if ($v->fails()) {
            Session::flash('error', $v->first());
            View::redirect('/superadmin/usuarios/' . $id);
        }

        if (!$this->service->redefinirSenha($id, $_POST['senha'])) {
            Session::flash('error', 'Não foi possível redefinir a senha.');
            View::redirect('/superadmin/usuarios');
        }

        $this->service->encerrarSessoes($id);
        AuditoriaService::registrar('superadmin_senha_redefinida', 'usuario', $id);
        Session::flash('success', 'Senha redefinida e sessões encerradas.');
        View::redirect('/superadmin/usuarios/' . $id);
    }

    public function usuarioBloquear(int $id): void
    {
        $meuId = (int) Session::get('usuario_id');
        if ($id === $meuId) {
            Session::flash('error', 'Você não pode bloquear a si mesmo.');
            View::redirect('/superadmin/usuarios/' . $id);
        }

        $bloqueado = $this->service->alternarBloqueio($id);
        AuditoriaService::registrar($bloqueado ? 'superadmin_usuario_bloqueado' : 'superadmin_usuario_desbloqueado', 'usuario', $id);
        Session::flash('success', $bloqueado ? 'Usuário bloqueado.' : 'Usuário desbloqueado.');
        View::redirect('/superadmin/usuarios/' . $id);
    }

    public function usuarioSessoes(int $id): void
    {
        $this->service->encerrarSessoes($id);
        AuditoriaService::registrar('superadmin_sessoes_encerradas', 'usuario', $id);
        Session::flash('success', 'Sessões e tokens encerrados.');
        View::redirect('/superadmin/usuarios/' . $id);
    }

    public function usuarioExcluir(int $id): void
    {
        $meuId = (int) Session::get('usuario_id');
        if ($id === $meuId) {
            Session::flash('error', 'Você não pode excluir a si mesmo.');
            View::redirect('/superadmin/usuarios/' . $id);
        }

        if (!$this->service->excluirUsuario($id)) {
            Session::flash('error', 'Não foi possível excluir o usuário.');
            View::redirect('/superadmin/usuarios');
        }

        AuditoriaService::registrar('superadmin_usuario_excluido', 'usuario', $id);
        Session::flash('success', 'Usuário excluído e anonimizado (LGPD).');
        View::redirect('/superadmin/usuarios');
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
            Session::flash('error', 'Loja inválida.');
            View::redirect('/superadmin/empresas');
        }

        $this->service->atualizarEmpresa($id, $_POST);
        AuditoriaService::registrar('superadmin_empresa_atualizada', 'empresa', $id, [
            'plano' => $_POST['plano'] ?? null,
            'ativo' => !empty($_POST['ativo']),
            'plano_ativo' => !empty($_POST['plano_ativo']),
        ]);
        Session::flash('success', 'Loja atualizada.');
        View::redirect('/superadmin/empresas');
    }

    public function alternarAtivo(): void
    {
        $id = (int) ($_POST['empresa_id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Loja inválida.');
            View::redirect('/superadmin/empresas');
        }

        $ativo = $this->service->alternarAtivo($id);
        AuditoriaService::registrar($ativo ? 'superadmin_loja_ativada' : 'superadmin_loja_desabilitada', 'empresa', $id);
        Session::flash('success', $ativo ? 'Loja reativada.' : 'Loja desabilitada.');
        View::redirect('/superadmin/empresas');
    }

    public function alternarPlano(): void
    {
        $id = (int) ($_POST['empresa_id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Loja inválida.');
            View::redirect('/superadmin/empresas');
        }

        $ativo = $this->service->alternarPlanoAtivo($id);
        AuditoriaService::registrar($ativo ? 'superadmin_plano_ativado' : 'superadmin_plano_desativado', 'empresa', $id);
        Session::flash('success', $ativo ? 'Plano ativado.' : 'Plano desativado.');
        View::redirect('/superadmin/empresas');
    }

    public function logins(): void
    {
        View::render('superadmin/logins', [
            'title' => 'Logins — Superadmin',
            'logins' => $this->service->listarLogins(),
        ]);
    }

    public function sistema(): void
    {
        View::render('superadmin/sistema', [
            'title' => 'Sistema — Superadmin',
            'logs' => \App\Core\Logger::tail(300),
            'migrations' => $this->service->statusMigrations(),
        ]);
    }

    public function promover(): void
    {
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            Session::flash('error', 'Usuário inválido.');
            View::redirect('/superadmin/usuarios');
        }

        \App\Core\App::pdo()->prepare('UPDATE usuarios SET is_superadmin = 1 WHERE id = :id')
            ->execute(['id' => $usuarioId]);

        AuditoriaService::registrar('superadmin_promovido', 'usuario', $usuarioId);
        Session::flash('success', 'Usuário promovido a superadmin.');
        View::redirect($_POST['redirect'] ?? '/superadmin/usuarios');
    }

    public function revogar(): void
    {
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $meuId = (int) Session::get('usuario_id');

        if ($usuarioId <= 0 || $usuarioId === $meuId) {
            Session::flash('error', 'Não é possível revogar este usuário.');
            View::redirect('/superadmin/usuarios');
        }

        \App\Core\App::pdo()->prepare('UPDATE usuarios SET is_superadmin = 0 WHERE id = :id')
            ->execute(['id' => $usuarioId]);

        AuditoriaService::registrar('superadmin_revogado', 'usuario', $usuarioId);
        Session::flash('success', 'Superadmin revogado.');
        View::redirect($_POST['redirect'] ?? '/superadmin/usuarios');
    }
}
