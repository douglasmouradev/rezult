<div class="page-header">
    <h1><i class="ph ph-wrench"></i> Sistema</h1>
    <p class="text-muted">Relógio do servidor, logs e status das migrations</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3 class="card-title">Data e hora do servidor</h3></div>
    <div style="padding:16px 20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        <div>
            <div class="text-muted" style="font-size:0.8rem">PHP (aplicação)</div>
            <strong style="font-size:1.25rem"><?= htmlspecialchars($relogio['php']) ?></strong>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.8rem">MySQL (sessão)</div>
            <strong style="font-size:1.25rem"><?= htmlspecialchars($relogio['mysql'] ?? '—') ?></strong>
        </div>
        <div>
            <div class="text-muted" style="font-size:0.8rem">Fuso configurado</div>
            <strong><?= htmlspecialchars($relogio['timezone']) ?></strong>
            <div class="td-muted">offset <?= htmlspecialchars($relogio['offset']) ?></div>
        </div>
    </div>
    <p class="text-muted" style="padding:0 20px 16px;font-size:13px;margin:0">
        Ajuste em <code>APP_TIMEZONE</code> no <code>.env</code> (padrão: <code>America/Sao_Paulo</code>).
    </p>
</div>

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
                    <td><?= $m['aplicado_em'] ? \App\Helpers\DateTimeBr::format($m['aplicado_em']) : '—' ?></td>
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
