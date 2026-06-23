<div class="page-header">
    <h1><i class="ph ph-wrench"></i> Sistema</h1>
    <p class="text-muted">Logs da aplicação e status das migrations</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Migrations</h3></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Arquivo</th><th>Status</th><th>Aplicado em</th></tr></thead>
                <tbody>
                <?php foreach ($migrations as $m): ?>
                <tr>
                    <td><code><?= htmlspecialchars($m['arquivo']) ?></code></td>
                    <td>
                        <?php if ($m['aplicado']): ?>
                        <span class="badge badge-pago">OK</span>
                        <?php else: ?>
                        <span class="badge badge-pendente">Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $m['aplicado_em'] ? date('d/m/Y H:i', strtotime($m['aplicado_em'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted" style="padding:12px 16px;font-size:13px">Execute <code>php bin/migrate.php</code> na VPS para aplicar pendentes.</p>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Log da aplicação</h3></div>
        <pre style="margin:0;padding:16px;max-height:480px;overflow:auto;font-size:12px;background:#0f172a;color:#e2e8f0;border-radius:0 0 8px 8px"><?php
            if (empty($logs)) {
                echo htmlspecialchars('Nenhum log registrado ainda.');
            } else {
                echo htmlspecialchars(implode("\n", $logs));
            }
        ?></pre>
    </div>
</div>
