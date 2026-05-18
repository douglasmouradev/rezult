<div class="page-header"><h1>Centros de custo</h1></div>
<form method="post" class="card mb-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="grid-3">
        <input class="input" name="nome" placeholder="Nome" required>
        <input class="input" name="codigo" placeholder="Código">
        <button class="btn btn-primary">Adicionar</button>
    </div>
</form>
<div class="card">
<table class="data-table">
<thead><tr><th>Código</th><th>Nome</th></tr></thead>
<tbody><?php foreach ($centros as $c): ?>
<tr><td><?= htmlspecialchars($c['codigo'] ?? '') ?></td><td><?= htmlspecialchars($c['nome']) ?></td></tr>
<?php endforeach; ?></tbody>
</table>
</div>
