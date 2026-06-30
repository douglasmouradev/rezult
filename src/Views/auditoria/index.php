<div class="page-header">
    <h1>Trilha de auditoria</h1>
    <p class="text-muted">Últimas 200 ações registradas</p>
</div>
<div class="card table-card">
<?php if (empty($registros)): ?>
    <?php
    $icone = 'clipboard-text';
    $titulo = 'Nenhum registro';
    $texto = 'Ações importantes da sua empresa aparecerão aqui.';
    $acaoUrl = null;
    require __DIR__ . '/../partials/empty-state.php';
    ?>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>IP</th></tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $r): ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?></td>
            <td><?= htmlspecialchars($r['usuario_nome'] ?? '—') ?></td>
            <td><code><?= htmlspecialchars($r['acao']) ?></code></td>
            <td><?= htmlspecialchars(($r['entidade'] ?? '') . ($r['entidade_id'] ? ' #' . $r['entidade_id'] : '')) ?></td>
            <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
