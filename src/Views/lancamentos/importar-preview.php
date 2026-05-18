<div class="page-header">
    <h1>Preview da importação</h1>
    <p class="text-muted"><?= count($preview['valid']) ?> válidas · <?= count($preview['invalid']) ?> com erro</p>
</div>
<?php if (!empty($preview['invalid'])): ?>
<div class="card card-danger mb-2">
    <h3>Linhas rejeitadas</h3>
    <table class="data-table"><thead><tr><th>Linha</th><th>Erro</th></tr></thead>
    <tbody><?php foreach ($preview['invalid'] as $inv): ?>
    <tr><td><?= (int)$inv['linha'] ?></td><td><?= htmlspecialchars($inv['erro']) ?></td></tr>
    <?php endforeach; ?></tbody></table>
</div>
<?php endif; ?>
<?php if (!empty($preview['valid'])): ?>
<div class="card">
    <h3>Prévia (primeiras linhas)</h3>
    <table class="data-table"><thead><tr><th>Data</th><th>Descrição</th><th>Tipo</th><th>Valor</th></tr></thead>
    <tbody><?php foreach (array_slice($preview['valid'], 0, 20) as $v): ?>
    <tr><td><?= htmlspecialchars($v['data']) ?></td><td><?= htmlspecialchars($v['descricao']) ?></td><td><?= $v['tipo'] ?></td><td><?= $v['valor'] ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <form method="post" action="/lancamentos/importar" class="mt-2">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <button type="submit" class="btn btn-primary">Confirmar importação</button>
        <a href="/lancamentos/importar" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php endif; ?>
