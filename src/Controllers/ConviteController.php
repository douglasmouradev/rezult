<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\AuthService;

final class ConviteController
{
    public function aceitarForm(string $token): void
    {
        $convite = $this->buscarConvite($token);
        if (!$convite) {
            Session::flash('error', 'Convite inválido ou expirado.');
            View::redirect('/login');
        }
        View::render('convite/aceitar', [
            'title' => 'Aceitar convite',
            'convite' => $convite,
            'token' => $token,
        ], layout: 'guest');
    }

    public function aceitar(string $token): void
    {
        $convite = $this->buscarConvite($token);
        if (!$convite) {
            Session::flash('error', 'Convite inválido ou expirado.');
            View::redirect('/login');
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->findByEmail($convite['email']);

        if (!$usuario) {
            $senha = $_POST['senha'] ?? '';
            if (strlen($senha) < 8) {
                Session::flash('error', 'Senha deve ter no mínimo 8 caracteres.');
                View::redirect("/convite/{$token}");
            }
            $uid = $usuarioModel->criar(Sanitize::raw($_POST['nome'] ?? $convite['email']), $convite['email'], $senha);
            App::pdo()->prepare('UPDATE usuarios SET email_verificado = 1 WHERE id = :id')->execute(['id' => $uid]);
            $usuario = $usuarioModel->findByEmail($convite['email']);
        }

        (new Empresa())->vincularUsuario((int) $usuario['id'], (int) $convite['empresa_id'], $convite['papel']);
        App::pdo()->prepare('UPDATE convites SET aceito_em = NOW() WHERE id = :id')->execute(['id' => $convite['id']]);

        (new AuthService())->login($usuario);
        AuditoriaService::registrar('convite_aceito', 'empresa', (int) $convite['empresa_id']);
        Session::flash('success', 'Bem-vindo à equipe!');
        View::redirect('/dashboard');
    }

    private function buscarConvite(string $token): ?array
    {
        $stmt = App::pdo()->prepare(
            'SELECT c.*, e.nome AS empresa_nome FROM convites c
             JOIN empresas e ON e.id = c.empresa_id
             WHERE c.token = :t AND c.aceito_em IS NULL AND c.expira_em > NOW() LIMIT 1'
        );
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
