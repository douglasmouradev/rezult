<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="page-header">
    <h1>Webhooks</h1>
    <p class="text-muted">Receba notificações HTTP com assinatura HMAC (header X-Rezult-Signature).</p>
    <a href="/webhooks/entregas" class="btn btn-ghost btn-sm">Ver entregas</a>
</div>

<form method="post" action="/webhooks" class="card mb-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <h3>Novo webhook</h3>
    <input class="input" type="url" name="url" placeholder="https://seu-servidor.com/webhook" required>
    <div class="mt-2">
        <?php foreach ($eventosDisponiveis as $ev): ?>
        <label class="checkbox-inline"><input type="checkbox" name="eventos[]" value="<?= htmlspecialchars($ev) ?>"> <?= htmlspecialchars($ev) ?></label>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-2">Salvar</button>
</form>

<div class="card">
<?php if (empty($webhooks)): ?>
    <?php
    $icone = 'broadcast';
    $titulo = 'Nenhum webhook';
    $texto = 'Configure URLs para receber eventos do Rezult em tempo real.';
    $acaoUrl = null;
    $acaoLabel = null;
    require __DIR__ . '/../partials/empty-state.php';
    ?>
<?php else: ?>
<table class="data-table">
<thead><tr><th>URL</th><th>Eventos</th><th>Ativo</th><th>Criado</th><th></th></tr></thead>
<tbody>
<?php foreach ($webhooks as $w):
    $eventos = json_decode($w['eventos'] ?? '[]', true) ?: [];
?>
<tr>
    <td><code><?= htmlspecialchars($w['url']) ?></code></td>
    <td><?= htmlspecialchars(implode(', ', $eventos)) ?></td>
    <td><?= !empty($w['ativo']) ? 'Sim' : 'Não' ?></td>
    <td><?= date('d/m/Y', strtotime($w['criado_em'])) ?></td>
    <td>
        <form method="post" action="/webhooks/<?= (int) $w['id'] ?>/excluir" data-confirm="Remover este webhook?">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button type="submit" class="btn-ghost btn-sm btn-action-danger">Excluir</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
