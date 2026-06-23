<div class="page-header">
    <h1>Histórico de logins</h1>
    <p class="text-muted">Últimas 300 tentativas de acesso (sucesso e falha)</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="card table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Data</th><th>E-mail</th><th>Usuário</th><th>IP</th><th>Resultado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($logins as $l): ?>
            <tr>
                <td><?= date('d/m/Y H:i:s', strtotime($l['criado_em'])) ?></td>
                <td><?= htmlspecialchars($l['email']) ?></td>
                <td><?= htmlspecialchars($l['usuario_nome'] ?? '—') ?></td>
                <td><code><?= htmlspecialchars($l['ip']) ?></code></td>
                <td>
                    <?php if ((int)$l['sucesso']): ?>
                    <span class="badge badge-pago">Sucesso</span>
                    <?php else: ?>
                    <span class="badge badge-pendente">Falha</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
