<div class="page-header"><h1>API — Tokens</h1><p class="text-muted">Use header: Authorization: Bearer SEU_TOKEN</p></div>
<?php if (!empty($novoToken)): ?>
<div class="card card-warning mb-2"><p><strong>Token gerado (copie agora):</strong></p><code><?= htmlspecialchars($novoToken) ?></code></div>
<?php endif; ?>
<form method="post" class="card mb-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <input class="input" name="nome" placeholder="Nome do token" required>
    <button class="btn btn-primary mt-2">Gerar token</button>
</form>
<div class="card">
<table class="data-table">
<thead><tr><th>Nome</th><th>Prefixo</th><th>Último uso</th><th>Criado</th></tr></thead>
<tbody><?php foreach ($tokens as $t): ?>
<tr><td><?= htmlspecialchars($t['nome']) ?></td><td><code><?= htmlspecialchars($t['prefixo']) ?>...</code></td><td><?= $t['ultimo_uso'] ? date('d/m/Y H:i', strtotime($t['ultimo_uso'])) : '—' ?></td><td><?= date('d/m/Y', strtotime($t['criado_em'])) ?></td></tr>
<?php endforeach; ?></tbody>
</table>
</div>
