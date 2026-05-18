<?php use App\Helpers\Money; ?>
<form class="filters card" method="get">
    <input type="date" name="de" class="input" value="<?= $periodo['de'] ?>">
    <input type="date" name="ate" class="input" value="<?= $periodo['ate'] ?>">
    <button type="submit" class="btn-primary btn-sm">Filtrar</button>
    <a href="?<?= http_build_query(array_merge($periodo, ['formato' => 'xlsx'])) ?>" class="btn btn-secondary btn-sm">Exportar Excel</a>
</form>
<div class="card table-wrap">
<table>
<thead><tr><th>Data</th><th>Entradas</th><th>Saídas</th><th>Saldo dia</th></tr></thead>
<tbody>
<?php foreach ($dados as $d): ?>
<tr>
    <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
    <td style="color:var(--green)"><?= Money::format((float)$d['entradas']) ?></td>
    <td style="color:var(--red)"><?= Money::format((float)$d['saidas']) ?></td>
    <td><?= Money::format((float)$d['entradas'] - (float)$d['saidas']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
