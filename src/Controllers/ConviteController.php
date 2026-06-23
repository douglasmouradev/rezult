<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\Validator;
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
        $usuarioExiste = (new Usuario())->findByEmail($convite['email']) !== null;
        View::render('convite/aceitar', [
            'title' => 'Aceitar convite',
            'convite' => $convite,
            'token' => $token,
            'usuarioExiste' => $usuarioExiste,
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
        $senha = $_POST['senha'] ?? '';

        if (!$usuario) {
            $v = new Validator($_POST);
            $v->required('nome', 'senha')->min('senha', 8)->password('senha');
            if ($v->fails()) {
                Session::flash('error', $v->first());
                View::redirect("/convite/{$token}");
            }
            $uid = $usuarioModel->criar(Sanitize::raw($_POST['nome']), $convite['email'], $senha);
            App::pdo()->prepare('UPDATE usuarios SET email_verificado = 1 WHERE id = :id')->execute(['id' => $uid]);
            $usuario = $usuarioModel->findByEmail($convite['email']);
        } else {
            if ($senha === '' || !$usuarioModel->verificarSenha($usuario, $senha)) {
                Session::flash('error', 'Informe a senha da sua conta para aceitar o convite.');
                View::redirect("/convite/{$token}");
            }
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
