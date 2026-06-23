<?php
$s = $stats;
$svc = new \App\Services\SuperAdminService();
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
                <td><?= htmlspecialchars($u['nome']) ?><?= (int)$u['is_superadmin'] ? ' <span class="badge">SA</span>' : '' ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= (int) $u['empresas_qtd'] ?></td>
                <td><?= $u['ultimo_login_em'] ? date('d/m/Y H:i', strtotime($u['ultimo_login_em'])) : '—' ?></td>
                <td>
                    <?php if ($svc->estaAtivo($u['ultimo_login_em'])): ?>
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
