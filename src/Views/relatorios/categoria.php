<?php use App\Helpers\Money; ?>
<form class="filters card" method="get">
    <input type="date" name="de" class="input" value="<?= $periodo['de'] ?>">
    <input type="date" name="ate" class="input" value="<?= $periodo['ate'] ?>">
    <select name="tipo" class="input"><option value="despesa" <?= $tipo==='despesa'?'selected':'' ?>>Despesa</option><option value="receita" <?= $tipo==='receita'?'selected':'' ?>>Receita</option></select>
    <button type="submit" class="btn-primary btn-sm">Filtrar</button>
    <a href="?<?= http_build_query(array_merge($periodo, ['tipo' => $tipo, 'formato' => 'xlsx'])) ?>" class="btn btn-secondary btn-sm">Exportar Excel</a>
</form>
<div class="grid-2">
    <div class="card table-wrap">
        <h3 style="margin-bottom:12px;font-family:Syne">Por categoria</h3>
        <table><thead><tr><th>Categoria</th><th>Total</th><th>Qtd</th></tr></thead>
        <tbody><?php foreach ($dados as $d): ?>
        <tr><td><?= htmlspecialchars($d['nome']) ?></td><td><?= Money::format((float)$d['total']) ?></td><td><?= $d['qtd'] ?></td></tr>
        <?php endforeach; ?></tbody></table>
    </div>
    <div class="card table-wrap">
        <h3 style="margin-bottom:12px;font-family:Syne">Por tag</h3>
        <table><thead><tr><th>Tag</th><th>Total</th></tr></thead>
        <tbody><?php foreach ($tags as $t): ?>
        <tr><td><?= htmlspecialchars($t['tag']) ?></td><td><?= Money::format((float)$t['total']) ?></td></tr>
        <?php endforeach; ?></tbody></table>
    </div>
</div>
