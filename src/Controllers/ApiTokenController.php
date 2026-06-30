<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Policies\TenantPolicy;
use App\Services\PlanService;

final class ApiTokenController
{
    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        TenantPolicy::abortUnlessCanManageConfig();
        $stmt = App::pdo()->prepare(
            'SELECT id, nome, prefixo, escopos, ultimo_uso, expira_em, criado_em FROM api_tokens WHERE empresa_id = :e AND usuario_id = :u'
        );
        $stmt->execute(['e' => $eid, 'u' => TenantPolicy::usuarioId()]);
        View::render('api/tokens', [
            'title' => 'API',
            'tokens' => $stmt->fetchAll(),
            'novoToken' => Session::pull('api_token_plain'),
            'limite' => (new PlanService())->limites()[(new PlanService())->planoEmpresa($eid)]['api_tokens'] ?? null,
        ]);
    }

    public function criar(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        $plan = new PlanService();
        if (!$plan->podeCriarTokenApi($eid)) {
            Session::flash('error', 'Limite de tokens API do seu plano atingido.');
            View::redirect('/api/tokens');
        }

        $uid = TenantPolicy::usuarioId();
        $escopos = ($_POST['escopos'] ?? '') === 'read' ? 'read' : 'read_write';
        $plain = bin2hex(random_bytes(24));
        $prefix = substr($plain, 0, 12);
        App::pdo()->prepare(
            'INSERT INTO api_tokens (usuario_id, empresa_id, nome, token_hash, prefixo, escopos, expira_em)
             VALUES (:u,:e,:n,:h,:p,:s, DATE_ADD(NOW(), INTERVAL 1 YEAR))'
        )->execute([
            'u' => $uid,
            'e' => $eid,
            'n' => Sanitize::raw($_POST['nome'] ?? 'Token API'),
            'h' => password_hash($plain, PASSWORD_BCRYPT),
            'p' => $prefix,
            's' => $escopos,
        ]);
        Session::set('api_token_plain', $plain);
        View::redirect('/api/tokens');
    }

    public function revogar(int $id): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        App::pdo()->prepare(
            'DELETE FROM api_tokens WHERE id = :id AND empresa_id = :e AND usuario_id = :u'
        )->execute(['id' => $id, 'e' => TenantPolicy::empresaId(), 'u' => TenantPolicy::usuarioId()]);
        Session::flash('success', 'Token revogado.');
        View::redirect('/api/tokens');
    }
}
