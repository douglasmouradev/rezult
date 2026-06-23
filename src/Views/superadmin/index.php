<?php
$s = $stats;
$svc = new \App\Services\SuperAdminService();
$maxLogin = max(1, ...array_column($loginsPorDia, 'total'));
?>
<div class="page-header">
    <h1><i class="ph ph-shield-star"></i> Superadmin</h1>
    <p class="text-muted">Visão global da plataforma — usuários, empresas e acessos</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="stats-grid" style="margin-bottom:24px">
    <?php foreach (
        [
            ['Usuários', $s['total_usuarios'], 'users'],
            ['Ativos (' . $s['ativos_dias'] . 'd)', $s['usuarios_ativos'], 'user-check'],
            ['Empresas', $s['total_empresas'], 'buildings'],
            ['Lojas c/ plano ativo', $s['empresas_plano_ativo'], 'storefront'],
            ['Lojas inativas', $s['empresas_desabilitadas'], 'prohibit'],
            ['Logins hoje', $s['logins_hoje'], 'sign-in'],
            ['Logins 7 dias', $s['logins_7d'], 'chart-line-up'],
            ['Logins 30 dias', $s['logins_30d'], 'calendar'],
            ['Falhas hoje', $s['falhas_hoje'], 'warning'],
            ['Cadastros 30d', $s['cadastros_30d'], 'user-plus'],
        ] as [$label, $value, $icon]
    ): ?>
    <div class="card stat-card stat-saldo">
        <div class="stat-head">
            <span class="stat-label"><?= htmlspecialchars($label) ?></span>
            <span class="stat-icon"><i class="ph ph-<?= $icon ?>"></i></span>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="font-size:1.75rem;font-weight:700"><?= (int) $value ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Logins (14 dias)</h3></div>
        <div style="padding:16px 20px;display:flex;align-items:flex-end;gap:6px;height:140px">
            <?php foreach ($loginsPorDia as $dia): ?>
            <?php $h = max(4, (int) round(($dia['total'] / $maxLogin) * 100)); ?>
            <div style="flex:1;text-align:center" title="<?= date('d/m', strtotime($dia['data'])) ?>: <?= (int) $dia['total'] ?>">
                <div style="background:#2563eb;border-radius:4px 4px 0 0;height:<?= $h ?>px;margin:0 auto;max-width:28px"></div>
                <small class="text-muted" style="font-size:10px"><?= date('d/m', strtotime($dia['data'])) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Planos expirando (7 dias)</h3>
            <a href="/superadmin/empresas" class="btn-ghost btn-sm">Ver lojas</a>
        </div>
        <?php if (empty($expirando)): ?>
        <p class="text-muted" style="padding:16px 20px">Nenhuma loja com plano expirando em breve.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Loja</th><th>Plano</th><th>Expira</th></tr></thead>
                <tbody>
                <?php foreach ($expirando as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['nome']) ?></td>
                    <td><?= htmlspecialchars($e['plano']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime((string) $e['plano_expira_em'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <h3 class="card-title">Usuários recentes</h3>
        <a href="/superadmin/usuarios" class="btn-ghost btn-sm">Ver todos</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Usuário</th><th>E-mail</th><th>Empresas</th><th>Último login</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentes as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['nome']) ?><?= !empty($u['is_superadmin']) ? ' <span class="badge">SA</span>' : '' ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= (int) $u['empresas_qtd'] ?></td>
                <td><?= !empty($u['ultimo_login_em']) ? date('d/m/Y H:i', strtotime($u['ultimo_login_em'])) : '—' ?></td>
                <td>
                    <?php if ($svc->estaAtivo($u['ultimo_login_em'] ?? null)): ?>
                    <span class="badge badge-pago">Ativo</span>
                    <?php else: ?>
                    <span class="badge badge-pendente">Inativo</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
