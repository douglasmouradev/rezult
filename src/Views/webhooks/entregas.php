<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="page-header">
    <h1>Entregas de Webhook</h1>
    <p class="text-muted">Histórico de envios HTTP — falhas são reprocessadas automaticamente pelo cron.</p>
</div>

<p><a href="/webhooks" class="btn btn-ghost btn-sm">← Voltar aos webhooks</a></p>

<div class="card">
<table class="data-table">
<thead><tr><th>Evento</th><th>URL</th><th>HTTP</th><th>Tentativas</th><th>Status</th><th>Data</th></tr></thead>
<tbody>
<?php if (empty($entregas)): ?>
<tr><td colspan="6" class="text-muted">Nenhuma entrega registrada ainda.</td></tr>
<?php endif; ?>
<?php foreach ($entregas as $e): ?>
<tr>
    <td><code><?= htmlspecialchars($e['evento']) ?></code></td>
    <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis"><code><?= htmlspecialchars($e['url']) ?></code></td>
    <td><?= $e['http_status'] !== null ? (int) $e['http_status'] : '—' ?></td>
    <td><?= (int) $e['tentativas'] ?></td>
    <td><?= !empty($e['sucesso']) ? '<span class="badge badge-pago">OK</span>' : '<span class="badge badge-pendente">Falha</span>' ?></td>
    <td><?= date('d/m/Y H:i', strtotime($e['criado_em'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
