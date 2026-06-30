<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Helpers\BearerToken;
use App\Helpers\Session;
use App\Services\PlanService;
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
               AND (at.expira_em IS NULL OR at.expira_em > NOW())'
        );
        $stmt->execute(['p' => $prefix]);
        $row = null;
        foreach ($stmt->fetchAll() as $candidate) {
            if (password_verify($token, $candidate['token_hash'])) {
                $row = $candidate;
                break;
            }
        }
        if (!$row) {
            $rate->registrar('api_auth', $ip);
            $this->jsonError(401, 'Unauthorized');
        }

        $empresaId = (int) $row['empresa_id'];
        $plan = new PlanService();
        if (!$plan->temFeature($empresaId, 'api')) {
            $this->jsonError(403, 'API not available on current plan');
        }

        if ($rate->excedido('api_use_' . $row['id'], (string) $row['id'], 1000, 60)) {
            $this->jsonError(429, 'API rate limit exceeded');
        }
        $rate->registrar('api_use_' . $row['id'], (string) $row['id']);

        $escopos = $row['escopos'] ?? 'read_write';
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $escopos === 'read') {
            $this->jsonError(403, 'Token is read-only');
        }

        Session::set('usuario_id', (int) $row['uid']);
        Session::set('empresa_id', $empresaId);
        Session::set('api_token_id', (int) $row['id']);
        Session::set('api_token_escopos', $escopos);
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
