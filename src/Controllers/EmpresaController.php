<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\Upload;
use App\Models\Empresa;
use App\Policies\TenantPolicy;
use App\Services\AuthService;
use App\Services\AuditoriaService;
use App\Services\MailService;

final class EmpresaController
{
    public function __construct(
        private Empresa $model = new Empresa(),
        private AuthService $auth = new AuthService(),
        private MailService $mail = new MailService(),
    ) {}

    public function index(): void
    {
        View::render('empresas/index', [
            'title' => 'Empresas',
            'empresas' => $this->model->listarPorUsuario(TenantPolicy::usuarioId()),
        ]);
    }

    public function criarForm(): void
    {
        View::render('empresas/form', ['title' => 'Nova empresa', 'empresa' => null]);
    }

    public function criar(): void
    {
        $usuarioId = TenantPolicy::usuarioId();
        $id = $this->model->save([
            'nome' => Sanitize::raw($_POST['nome']),
            'cnpj' => Sanitize::raw($_POST['cnpj'] ?? ''),
            'moeda' => $_POST['moeda'] ?? 'BRL',
        ]);

        if (!empty($_FILES['logo']['name'])) {
            $logo = Upload::store($_FILES['logo'], 'logos', $id);
            if ($logo) {
                $this->model->save(['id' => $id, 'logo_url' => '/arquivo?path=' . urlencode($logo)]);
            }
        }

        $this->model->vincularUsuario($usuarioId, $id, 'dono');
        $this->auth->definirEmpresaAtiva($id, $this->model->listarPorUsuario($usuarioId));
        AuditoriaService::registrar('empresa_criada', 'empresa', $id);
        Session::flash('success', 'Empresa criada!');
        View::redirect('/dashboard');
    }

    public function editarForm(int $id): void
    {
        TenantPolicy::abortUnlessCanManageEmpresa($id);
        View::render('empresas/form', [
            'title' => 'Editar empresa',
            'empresa' => $this->model->find($id),
        ]);
    }

    public function editar(int $id): void
    {
        TenantPolicy::abortUnlessCanManageEmpresa($id);
        $data = [
            'id' => $id,
            'nome' => Sanitize::raw($_POST['nome']),
            'cnpj' => Sanitize::raw($_POST['cnpj'] ?? ''),
            'moeda' => $_POST['moeda'] ?? 'BRL',
        ];
        if (!empty($_FILES['logo']['name'])) {
            $logo = Upload::store($_FILES['logo'], 'logos', $id);
            if ($logo) {
                $data['logo_url'] = '/arquivo?path=' . urlencode($logo);
            }
        }
        $this->model->save($data);
        AuditoriaService::registrar('empresa_atualizada', 'empresa', $id);
        Session::flash('success', 'Empresa atualizada.');
        View::redirect('/empresas');
    }

    public function trocar(int $id): void
    {
        TenantPolicy::abortUnlessEmpresaAccess($id);
        $this->auth->definirEmpresaAtiva($id);
        $allowed = ['/dashboard', '/lancamentos', '/contas', '/categorias', '/metas', '/relatorios/dre', '/empresas'];
        $path = parse_url($_SERVER['HTTP_REFERER'] ?? '/dashboard', PHP_URL_PATH) ?: '/dashboard';
        if (!in_array($path, $allowed, true) && !str_starts_with($path, '/relatorios/')) {
            $path = '/dashboard';
        }
        View::redirect($path);
    }

    public function convidar(int $id): void
    {
        TenantPolicy::abortUnlessCanManageEmpresa($id);
        $papel = in_array($_POST['papel'] ?? '', ['admin', 'operador'], true) ? $_POST['papel'] : 'operador';
        $email = Sanitize::raw($_POST['email']);
        $token = bin2hex(random_bytes(32));

        \App\Core\App::pdo()->prepare(
            'INSERT INTO convites (empresa_id, email, papel, token, convidado_por, expira_em)
             VALUES (:e, :email, :p, :t, :u, DATE_ADD(NOW(), INTERVAL 7 DAY))'
        )->execute([
            'e' => $id,
            'email' => $email,
            'p' => $papel,
            't' => $token,
            'u' => TenantPolicy::usuarioId(),
        ]);

        $link = \App\Core\App::config('url') . "/convite/{$token}";
        $this->mail->enviar($email, 'Convite Rezult', "Você foi convidado. Acesse: {$link}");
        AuditoriaService::registrar('convite_enviado', 'empresa', $id, ['email' => $email]);

        Session::flash('success', 'Convite enviado!');
        View::redirect('/empresas');
    }
}
