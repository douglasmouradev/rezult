<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Models\Usuario;
use App\Services\AuthService;
use App\Services\RateLimitService;

final class AuthController
{
    private ?AuthService $auth = null;
    private ?Usuario $usuarios = null;

    private function auth(): AuthService
    {
        return $this->auth ??= new AuthService();
    }

    private function usuarios(): Usuario
    {
        return $this->usuarios ??= new Usuario();
    }

    public function loginForm(): void
    {
        View::render('auth/login', ['title' => 'Entrar'], layout: 'guest');
    }

    public function login(): void
    {
        $email = Sanitize::raw($_POST['email'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $rate = new RateLimitService();
        if ($rate->excedido('login_ip', $ip, 30, 15)) {
            Session::flash('error', 'Muitas tentativas deste IP. Aguarde 15 minutos.');
            View::redirect('/login');
        }
        $rate->registrar('login_ip', $ip);

        if ($this->auth()->tentativasExcedidas($email, $ip)) {
            Session::flash('error', 'Muitas tentativas. Aguarde 15 minutos.');
            View::redirect('/login');
        }

        $usuario = $this->usuarios()->findByEmail($email);
        $ok = $usuario && $this->usuarios()->verificarSenha($usuario, $_POST['senha'] ?? '');

        $this->auth()->registrarTentativa($email, $ip, $ok);

        if (!$ok) {
            Session::flash('error', 'E-mail ou senha incorretos.');
            View::redirect('/login');
        }

        if (!empty($usuario['bloqueado'])) {
            Session::flash('error', 'Conta bloqueada. Entre em contato com o suporte.');
            View::redirect('/login');
        }

        if (!(int) $usuario['email_verificado']) {
            Session::flash('error', 'Confirme seu e-mail antes de entrar.');
            View::redirect('/login');
        }

        $this->auth()->login($usuario, !empty($_POST['lembrar']));
        View::redirect($this->auth()->rotaPosLogin());
    }

    public function registerForm(): void
    {
        View::render('auth/register', ['title' => 'Criar conta'], layout: 'guest');
    }

    public function register(): void
    {
        $email = Sanitize::raw($_POST['email'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rate = new RateLimitService();
        if ($rate->excedido('cadastro', $ip . ':' . $email)) {
            Session::flash('error', 'Muitas tentativas. Aguarde 15 minutos.');
            View::redirect('/cadastro');
        }
        $rate->registrar('cadastro', $ip . ':' . $email);

        $v = new Validator($_POST);
        $v->required('nome', 'email', 'senha', 'senha_confirmacao')
            ->email('email')->min('senha', 8)->password('senha');

        if ($_POST['senha'] !== ($_POST['senha_confirmacao'] ?? '')) {
            Session::flash('error', 'As senhas não coincidem.');
            View::redirect('/cadastro');
        }

        if (empty($_POST['aceite_termos']) || empty($_POST['aceite_privacidade'])) {
            Session::flash('error', 'É necessário aceitar os Termos e a Política de Privacidade (LGPD).');
            View::redirect('/cadastro');
        }

        if ($v->fails() || $this->usuarios()->findByEmail($_POST['email'])) {
            Session::flash('error', $v->first() ?? 'E-mail já cadastrado.');
            View::redirect('/cadastro');
        }

        $id = $this->auth()->registrar(
            Sanitize::raw($_POST['nome']),
            Sanitize::raw($_POST['email']),
            $_POST['senha']
        );
        (new \App\Services\LgpdService())->registrarConsentimentos($id, !empty($_POST['marketing_optin']));
        Session::flash('success', 'Conta criada! Verifique seu e-mail para confirmar.');
        View::redirect('/login');
    }

    public function logout(): void
    {
        $this->auth()->logout();
        View::redirect('/login');
    }

    public function confirmarEmail(): void
    {
        $this->usarToken($_GET['token'] ?? '', 'confirmacao', function ($row) {
            App::pdo()->prepare('UPDATE usuarios SET email_verificado = 1 WHERE id = :id')
                ->execute(['id' => $row['usuario_id']]);
            Session::flash('success', 'E-mail confirmado com sucesso!');
        });
        View::redirect('/login');
    }

    public function recuperarForm(): void
    {
        View::render('auth/recuperar', ['title' => 'Recuperar senha'], layout: 'guest');
    }

    public function recuperar(): void
    {
        $email = Sanitize::raw($_POST['email'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rate = new RateLimitService();
        if ($rate->excedido('recuperar', $ip . ':' . $email)) {
            Session::flash('error', 'Muitas tentativas. Aguarde 15 minutos.');
            View::redirect('/recuperar');
        }
        $rate->registrar('recuperar', $ip . ':' . $email);

        $usuario = $this->usuarios()->findByEmail($email);
        if ($usuario) {
            $this->auth()->criarTokenEmail((int) $usuario['id'], 'recuperacao');
        }
        Session::flash('success', 'Se o e-mail existir, enviaremos instruções.');
        View::redirect('/login');
    }

    public function redefinirForm(): void
    {
        View::render('auth/redefinir', ['title' => 'Nova senha', 'token' => $_GET['token'] ?? ''], layout: 'guest');
    }

    public function redefinir(): void
    {
        $token = $_POST['token'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $v = new Validator($_POST);
        $v->required('senha', 'senha_confirmacao')->min('senha', 8)->password('senha');
        if (($_POST['senha'] ?? '') !== ($_POST['senha_confirmacao'] ?? '')) {
            Session::flash('error', 'As senhas não coincidem.');
            View::redirect('/redefinir?token=' . urlencode($token));
        }
        if ($v->fails()) {
            Session::flash('error', $v->first());
            View::redirect('/redefinir?token=' . urlencode($token));
        }
        $this->usarToken($token, 'recuperacao', function ($row) use ($senha) {
            App::pdo()->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id')->execute([
                'h' => password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]),
                'id' => $row['usuario_id'],
            ]);
            Session::flash('success', 'Senha atualizada!');
        });
        View::redirect('/login');
    }

    private function usarToken(string $token, string $tipo, callable $callback): void
    {
        if ($token === '') {
            Session::flash('error', 'Token inválido.');
            return;
        }
        $stmt = App::pdo()->prepare(
            'SELECT * FROM tokens_email WHERE token = :t AND tipo = :tipo AND usado_em IS NULL AND expira_em > NOW()'
        );
        $stmt->execute(['t' => $token, 'tipo' => $tipo]);
        $row = $stmt->fetch();
        if (!$row) {
            Session::flash('error', 'Link expirado ou inválido.');
            return;
        }
        $callback($row);
        App::pdo()->prepare('UPDATE tokens_email SET usado_em = NOW() WHERE id = :id')
            ->execute(['id' => $row['id']]);
    }
}
