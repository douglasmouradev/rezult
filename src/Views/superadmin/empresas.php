<?php
$svc = new \App\Services\SuperAdminService();
$planSvc = new \App\Services\PlanService();
?>
<div class="page-header">
    <h1>Lojas</h1>
    <p class="text-muted">Gerencie planos, validação e status das lojas cadastradas</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="filters card" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <span class="text-muted">Filtrar:</span>
    <a href="/superadmin/empresas" class="btn btn-sm <?= $filtro === '' ? 'btn-primary' : 'btn-ghost' ?>">Todas</a>
    <a href="/superadmin/empresas?filtro=ativo" class="btn btn-sm <?= $filtro === 'ativo' ? 'btn-primary' : 'btn-ghost' ?>">Plano ativo</a>
    <a href="/superadmin/empresas?filtro=inativo" class="btn btn-sm <?= $filtro === 'inativo' ? 'btn-primary' : 'btn-ghost' ?>">Inativas / desabilitadas</a>
</div>

<div class="card table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Loja</th>
                    <th>Dono</th>
                    <th>Plano</th>
                    <th>Status</th>
                    <th>Expira</th>
                    <th>Membros</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($empresas as $e):
                $status = $svc->statusPlano($e);
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($e['nome']) ?></strong>
                    <div class="td-muted">#<?= (int) $e['id'] ?> · <?= htmlspecialchars($e['cnpj'] ?? '—') ?></div>
                </td>
                <td>
                    <?= htmlspecialchars($e['dono_nome'] ?? '—') ?>
                    <div class="td-muted"><?= htmlspecialchars($e['dono_email'] ?? '') ?></div>
                </td>
                <td>
                    <form method="post" action="/superadmin/empresas/atualizar" class="inline-form" style="display:flex;gap:6px;align-items:center">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="empresa_id" value="<?= (int) $e['id'] ?>">
                        <input type="hidden" name="ativo" value="<?= (int)($e['ativo'] ?? 1) ?>">
                        <input type="hidden" name="plano_ativo" value="<?= (int)($e['plano_ativo'] ?? 1) ?>">
                        <select name="plano" class="input btn-sm" onchange="this.form.submit()">
                            <?php foreach (['starter', 'pro', 'business'] as $p): ?>
                            <option value="<?= $p ?>" <?= ($e['plano'] ?? 'starter') === $p ? 'selected' : '' ?>><?= $planSvc->planoLabel($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <?php if ($status === 'ativa'): ?>
                    <span class="badge badge-pago">Plano ativo</span>
                    <?php elseif ($status === 'desabilitada'): ?>
                    <span class="badge badge-pendente">Desabilitada</span>
                    <?php else: ?>
                    <span class="badge badge-pendente">Plano inativo</span>
                    <?php endif; ?>
                    <?php if (!(int)($e['ativo'] ?? 1)): ?>
                    <span class="badge" style="margin-left:4px">Loja off</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" action="/superadmin/empresas/atualizar" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="empresa_id" value="<?= (int) $e['id'] ?>">
                        <input type="hidden" name="plano" value="<?= htmlspecialchars($e['plano'] ?? 'starter') ?>">
                        <input type="hidden" name="ativo" value="<?= (int)($e['ativo'] ?? 1) ?>">
                        <input type="hidden" name="plano_ativo" value="<?= (int)($e['plano_ativo'] ?? 1) ?>">
                        <input type="date" name="plano_expira_em" class="input btn-sm" value="<?= !empty($e['plano_expira_em']) ? date('Y-m-d', strtotime($e['plano_expira_em'])) : '' ?>" onchange="this.form.submit()">
                    </form>
                </td>
                <td><?= (int) $e['membros_qtd'] ?></td>
                <td style="white-space:nowrap">
                    <form method="post" action="/superadmin/empresas/plano" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="empresa_id" value="<?= (int) $e['id'] ?>">
                        <button type="submit" class="btn-ghost btn-sm" title="Ativar/desativar plano">
                            <?= (int)($e['plano_ativo'] ?? 1) ? 'Desativar plano' : 'Ativar plano' ?>
                        </button>
                    </form>
                    <form method="post" action="/superadmin/empresas/status" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="empresa_id" value="<?= (int) $e['id'] ?>">
                        <button type="submit" class="btn-ghost btn-sm" title="Habilitar/desabilitar loja">
                            <?= (int)($e['ativo'] ?? 1) ? 'Desabilitar loja' : 'Reativar loja' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
