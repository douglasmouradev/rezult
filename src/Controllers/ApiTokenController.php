<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Policies\TenantPolicy;

final class ApiTokenController
{
    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        TenantPolicy::abortUnlessCanManageConfig();
        $stmt = App::pdo()->prepare(
            'SELECT id, nome, prefixo, ultimo_uso, expira_em, criado_em FROM api_tokens WHERE empresa_id = :e AND usuario_id = :u'
        );
        $stmt->execute(['e' => $eid, 'u' => TenantPolicy::usuarioId()]);
        View::render('api/tokens', [
            'title' => 'API',
            'tokens' => $stmt->fetchAll(),
            'novoToken' => Session::pull('api_token_plain'),
        ]);
    }

    public function criar(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        $uid = TenantPolicy::usuarioId();
        $plain = bin2hex(random_bytes(24));
        $prefix = substr($plain, 0, 12);
        App::pdo()->prepare(
            'INSERT INTO api_tokens (usuario_id, empresa_id, nome, token_hash, prefixo, expira_em)
             VALUES (:u,:e,:n,:h,:p, DATE_ADD(NOW(), INTERVAL 1 YEAR))'
        )->execute([
            'u' => $uid,
            'e' => $eid,
            'n' => Sanitize::raw($_POST['nome'] ?? 'Token API'),
            'h' => password_hash($plain, PASSWORD_BCRYPT),
            'p' => $prefix,
        ]);
        Session::set('api_token_plain', $plain);
        View::redirect('/api/tokens');
    }
}
