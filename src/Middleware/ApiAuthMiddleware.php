<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Helpers\BearerToken;
use App\Helpers\Session;
use App\Services\RateLimitService;
use Closure;

final class ApiAuthMiddleware
{
    public function __invoke(Closure $next): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rate = new RateLimitService();
        if ($rate->excedido('api_auth', $ip, 120, 1)) {
            $this->jsonError(429, 'Too many requests');
        }

        $token = BearerToken::fromRequest();
        if ($token === null) {
            $this->jsonError(401, 'Unauthorized');
        }

        $prefix = substr($token, 0, 12);
        $stmt = App::pdo()->prepare(
            'SELECT at.*, u.id AS uid FROM api_tokens at
             JOIN usuarios u ON u.id = at.usuario_id
             WHERE at.prefixo = :p
               AND (at.expira_em IS NULL OR at.expira_em > NOW())
             LIMIT 1'
        );
        $stmt->execute(['p' => $prefix]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($token, $row['token_hash'])) {
            $rate->registrar('api_auth', $ip);
            $this->jsonError(401, 'Unauthorized');
        }

        Session::set('usuario_id', (int) $row['uid']);
        Session::set('empresa_id', (int) $row['empresa_id']);
        App::pdo()->prepare('UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = :id')->execute(['id' => $row['id']]);
        $next();
    }

    private function jsonError(int $code, string $message): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
