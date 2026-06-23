<div class="page-header"><h1>Orçamento vs realizado</h1></div>
<form class="filters card" method="get">
    <input type="month" name="mes" class="input" value="<?= htmlspecialchars($mes) ?>">
    <button class="btn btn-primary btn-sm">Filtrar</button>
</form>
<?php if ($podeGerenciar): ?>
<form method="post" class="card mb-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <input type="hidden" name="mes" value="<?= htmlspecialchars($mes) ?>">
    <div class="grid-3">
        <select name="categoria_id" class="input" required>
            <?php foreach ($categorias as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo'] ?>)</option><?php endforeach; ?>
        </select>
        <input class="input" name="valor_planejado" placeholder="Valor planejado" required>
        <button class="btn btn-primary">Adicionar</button>
    </div>
</form>
<?php endif; ?>
<div class="card table-card">
<table class="data-table">
<thead><tr><th>Categoria</th><th>Planejado</th><th>Realizado</th><th>%</th><?php if ($podeGerenciar): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($itens as $i):
    $pct = $i['valor_planejado'] > 0 ? round(((float)$i['realizado'] / (float)$i['valor_planejado']) * 100) : 0;
?>
<tr>
    <td><?= htmlspecialchars($i['categoria_nome'] ?? '—') ?></td>
    <td>R$ <?= number_format((float)$i['valor_planejado'], 2, ',', '.') ?></td>
    <td>R$ <?= number_format((float)$i['realizado'], 2, ',', '.') ?></td>
    <td><?= $pct ?>%</td>
    <?php if ($podeGerenciar): ?>
    <td>
        <form method="post" action="/orcamentos/<?= (int)$i['id'] ?>/excluir" data-confirm="Remover esta linha?">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button type="submit" class="btn-ghost btn-sm">Excluir</button>
        </form>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
