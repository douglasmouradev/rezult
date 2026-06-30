<div class="page-header"><h1>API — Tokens</h1><p class="text-muted">Use header: Authorization: Bearer SEU_TOKEN</p></div>
<?php if (!empty($novoToken)): ?>
<div class="card card-warning mb-2"><p><strong>Token gerado (copie agora):</strong></p><code><?= htmlspecialchars($novoToken) ?></code></div>
<?php endif; ?>
<form method="post" action="/api/tokens" class="card mb-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <input class="input" name="nome" placeholder="Nome do token" required>
    <div class="form-group mt-2">
        <label>Escopo</label>
        <select name="escopos" class="input">
            <option value="read_write">Leitura e escrita</option>
            <option value="read">Somente leitura</option>
        </select>
    </div>
    <?php if (isset($limite)): ?>
    <p class="text-muted" style="font-size:0.85rem">Limite do plano: <?= $limite === null ? 'ilimitado' : (int) $limite ?> token(s)</p>
    <?php endif; ?>
    <button class="btn btn-primary mt-2">Gerar token</button>
</form>
<div class="card">
<table class="data-table">
<thead><tr><th>Nome</th><th>Prefixo</th><th>Escopo</th><th>Último uso</th><th>Criado</th><th></th></tr></thead>
<tbody><?php foreach ($tokens as $t): ?>
<tr>
    <td><?= htmlspecialchars($t['nome']) ?></td>
    <td><code><?= htmlspecialchars($t['prefixo']) ?>...</code></td>
    <td><?= ($t['escopos'] ?? 'read_write') === 'read' ? 'Leitura' : 'Leitura/escrita' ?></td>
    <td><?= $t['ultimo_uso'] ? date('d/m/Y H:i', strtotime($t['ultimo_uso'])) : '—' ?></td>
    <td><?= date('d/m/Y', strtotime($t['criado_em'])) ?></td>
    <td>
        <form method="post" action="/api/tokens/<?= (int) $t['id'] ?>/revogar" data-confirm="Revogar este token?">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button type="submit" class="btn-ghost btn-sm btn-action-danger">Revogar</button>
        </form>
    </td>
</tr>
<?php endforeach; ?></tbody>
</table>
</div>
