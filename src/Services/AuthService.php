<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\Session;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Helpers\MailTemplate;
use App\Services\MailService;

final class AuthService
{
    public function __construct(
        private Usuario $usuarios = new Usuario(),
        private Empresa $empresas = new Empresa(),
    ) {}

    public function registrar(string $nome, string $email, string $senha): int
    {
        $id = $this->usuarios->criar($nome, $email, $senha);
        $this->criarTokenEmail($id, 'confirmacao');
        return $id;
    }

    public function login(array $usuario, bool $lembrar = false): void
    {
        SuperAdminService::sincronizarSuperadminConfig((int) $usuario['id'], (string) $usuario['email']);

        Session::regenerate();
        Session::set('usuario_id', $usuario['id']);
        Session::set('usuario', [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'avatar_url' => $usuario['avatar_url'],
        ]);

        $isSuper = $this->usuarios->find((int) $usuario['id']);
        Session::set('is_superadmin', (int) ($isSuper['is_superadmin'] ?? 0) === 1);

        $this->registrarAcesso((int) $usuario['id']);

        $lista = $this->empresas->listarPorUsuario((int) $usuario['id']);
        Session::set('empresas', $lista);

        if (!empty($lista)) {
            $plan = new PlanService();
            $primeira = null;
            foreach ($lista as $e) {
                if ($plan->empresaOperacional($e)) {
                    $primeira = $e;
                    break;
                }
            }
            if ($primeira) {
                $this->definirEmpresaAtiva((int) $primeira['id'], $lista);
            }
        }

        if ($lembrar) {
            $this->invalidarRememberTokens((int) $usuario['id']);
            $this->criarRememberToken((int) $usuario['id']);
        } else {
            $this->invalidarRememberTokens((int) $usuario['id']);
        }
    }

    /** Rota adequada após login (evita loop em /empresas/criar). */
    public function rotaPosLogin(): string
    {
        if (\App\Policies\SuperAdminPolicy::isSuperadmin() && !Session::get('empresa_id')) {
            $lista = Session::get('empresas', []);
            if (empty($lista)) {
                return '/superadmin';
            }
        }

        $lista = Session::get('empresas', []);
        if (empty($lista)) {
            return '/empresas/criar';
        }

        if (Session::get('empresa_id')) {
            return '/dashboard';
        }

        return '/empresas';
    }

    public function definirEmpresaAtiva(int $empresaId, ?array $lista = null): void
    {
        $usuarioId = (int) Session::get('usuario_id');
        $lista ??= $this->empresas->listarPorUsuario($usuarioId);

        foreach ($lista as $e) {
            if ((int) $e['id'] === $empresaId) {
                $plan = new PlanService();
                if (!$plan->empresaOperacional($e) && !\App\Policies\SuperAdminPolicy::isSuperadmin()) {
                    Session::flash('error', $plan->motivoBloqueio($e) ?? 'Loja indisponível.');
                    return;
                }
                Session::regenerate();
                Session::set('empresa_id', $empresaId);
                Session::set('empresa', $e);
                Session::set('empresas', $lista);
                unset($_SESSION['dashboard_cache'][$empresaId]);
                return;
            }
        }
    }

    public function logout(): void
    {
        if (isset($_COOKIE['remember'])) {
            $parts = explode(':', $_COOKIE['remember'], 2);
            if (count($parts) === 2) {
                $this->invalidarRememberSelector($parts[0]);
            }
            setcookie('remember', '', ['expires' => time() - 3600, 'path' => '/']);
        }
        unset($_SESSION['api_token_plain']);
        session_destroy();
    }

    public function tentativasExcedidas(string $email, string $ip): bool
    {
        $stmt = App::pdo()->prepare(
            "SELECT COUNT(*) FROM login_tentativas
             WHERE email = :email AND ip = :ip AND sucesso = 0
             AND criado_em > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
        return (int) $stmt->fetchColumn() >= 5;
    }

    public function registrarTentativa(string $email, string $ip, bool $sucesso): void
    {
        $stmt = App::pdo()->prepare(
            'INSERT INTO login_tentativas (email, ip, sucesso) VALUES (:e, :i, :s)'
        );
        $stmt->execute(['e' => $email, 'i' => $ip, 's' => $sucesso ? 1 : 0]);
    }

    public function criarTokenEmail(int $usuarioId, string $tipo): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt = App::pdo()->prepare(
            'INSERT INTO tokens_email (usuario_id, token, tipo, expira_em) VALUES (:u, :t, :tipo, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
        );
        $stmt->execute(['u' => $usuarioId, 't' => $token, 'tipo' => $tipo]);
        // Em produção: enviar e-mail. Em dev, logamos na sessão.
        $path = match ($tipo) {
            'confirmacao' => '/auth/confirmacao',
            'recuperacao' => '/redefinir',
            default => '/login',
        };
        $link = App::config('url') . $path . '?token=' . $token;
        $usuario = App::pdo()->prepare('SELECT email, nome FROM usuarios WHERE id = :id');
        $usuario->execute(['id' => $usuarioId]);
        $u = $usuario->fetch();
        if ($u) {
            $tpl = match ($tipo) {
                'confirmacao' => MailTemplate::confirmacao($u['nome'], $link),
                'recuperacao' => MailTemplate::recuperacao($u['nome'], $link),
                default => ['subject' => 'Rezult — ' . $tipo, 'html' => '<p>' . htmlspecialchars($link) . '</p>', 'text' => $link],
            };
            (new MailService())->enviarTemplate($u['email'], $tpl);
        }
        if (App::config('debug') && App::config('env') !== 'production') {
            \App\Core\Logger::info('Token e-mail dev', ['tipo' => $tipo, 'usuario_id' => $usuarioId]);
        }
        return $token;
    }

    private function criarRememberToken(int $usuarioId): void
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $hash = password_hash($validator, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = App::pdo()->prepare(
            'INSERT INTO remember_tokens (usuario_id, selector, token_hash, expira_em)
             VALUES (:u, :s, :h, DATE_ADD(NOW(), INTERVAL 30 DAY))'
        );
        $stmt->execute(['u' => $usuarioId, 's' => $selector, 'h' => $hash]);

        setcookie('remember', "{$selector}:{$validator}", [
            'expires' => time() + 30 * 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => \App\Helpers\Session::requestIsHttps(),
        ]);
    }

    public function tentarRememberLogin(): bool
    {
        if (!isset($_COOKIE['remember'])) {
            return false;
        }
        $parts = explode(':', $_COOKIE['remember'], 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$selector, $validator] = $parts;
        $stmt = App::pdo()->prepare(
            'SELECT rt.*, u.* FROM remember_tokens rt
             JOIN usuarios u ON u.id = rt.usuario_id
             WHERE rt.selector = :s AND rt.expira_em > NOW() LIMIT 1'
        );
        $stmt->execute(['s' => $selector]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($validator, $row['token_hash'])) {
            return false;
        }

        $this->invalidarRememberSelector($selector);
        $this->login($row, lembrar: true);
        return true;
    }

    private function invalidarRememberTokens(int $usuarioId): void
    {
        App::pdo()->prepare('DELETE FROM remember_tokens WHERE usuario_id = :u')->execute(['u' => $usuarioId]);
    }

    private function invalidarRememberSelector(string $selector): void
    {
        App::pdo()->prepare('DELETE FROM remember_tokens WHERE selector = :s')->execute(['s' => $selector]);
    }

    private function registrarAcesso(int $usuarioId): void
    {
        try {
            App::pdo()->prepare('UPDATE usuarios SET ultimo_login_em = NOW() WHERE id = :id')
                ->execute(['id' => $usuarioId]);
        } catch (\Throwable) {
            // Coluna pode não existir antes da migration 011
        }
    }
}
