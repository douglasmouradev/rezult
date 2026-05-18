<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Policies\TenantPolicy;

final class AuditoriaController
{
    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        TenantPolicy::abortUnlessCanManageConfig($eid);

        $stmt = App::pdo()->prepare(
            'SELECT a.*, u.nome AS usuario_nome FROM auditoria a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.empresa_id = :e OR (a.empresa_id IS NULL AND a.usuario_id IN (
               SELECT usuario_id FROM usuario_empresa WHERE empresa_id = :e2
             ))
             ORDER BY a.criado_em DESC LIMIT 200'
        );
        $stmt->execute(['e' => $eid, 'e2' => $eid]);

        View::render('auditoria/index', [
            'title' => 'Auditoria',
            'registros' => $stmt->fetchAll(),
        ]);
    }
}
