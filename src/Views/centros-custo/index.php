<div class="page-header"><h1>Centros de custo</h1></div>
<form method="post" action="/centros-custo" class="card mb-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="grid-3">
        <input class="input" name="nome" placeholder="Nome" required>
        <input class="input" name="codigo" placeholder="Código">
        <button class="btn btn-primary">Adicionar</button>
    </div>
</form>
<div class="card">
<?php if (empty($centros)): ?>
    <?php
    $icone = 'tree-structure';
    $titulo = 'Nenhum centro de custo';
    $texto = 'Organize despesas e receitas por departamento ou projeto.';
    $acaoUrl = null;
    require __DIR__ . '/../partials/empty-state.php';
    ?>
<?php else: ?>
<table class="data-table">
<thead><tr><th>Código</th><th>Nome</th><th class="th-actions">Ações</th></tr></thead>
<tbody><?php foreach ($centros as $c): ?>
<tr>
    <td><?= htmlspecialchars($c['codigo'] ?? '') ?></td>
    <td><?= htmlspecialchars($c['nome']) ?></td>
    <td>
        <div class="row-actions">
            <form method="post" action="/centros-custo" class="inline-form" style="display:inline-flex;gap:4px">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input class="input btn-sm" name="nome" value="<?= htmlspecialchars($c['nome']) ?>" required>
                <input class="input btn-sm" name="codigo" value="<?= htmlspecialchars($c['codigo'] ?? '') ?>">
                <button type="submit" class="btn-ghost btn-sm" title="Salvar">Salvar</button>
            </form>
            <form method="post" action="/centros-custo/<?= (int) $c['id'] ?>/excluir" class="inline-form" data-confirm="Remover este centro de custo?">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn-ghost btn-sm btn-action-danger">Excluir</button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; ?></tbody>
</table>
<?php endif; ?>
</div>
