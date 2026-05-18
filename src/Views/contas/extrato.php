<?php use App\Helpers\Money; ?>
<div class="card" style="margin-bottom:20px">
    <h3><?= htmlspecialchars($conta['nome']) ?> — Saldo: <?= Money::format($saldo) ?></h3>
</div>
<div class="card table-wrap">
<table>
<thead><tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Saldo acumulado</th></tr></thead>
<tbody>
<?php foreach ($movimentos as $m): ?>
<tr>
    <td><?= date('d/m/Y', strtotime($m['data_lancamento'])) ?></td>
    <td><?= htmlspecialchars($m['descricao']) ?></td>
    <td><?= Money::format((float)$m['valor_signed']) ?></td>
    <td><?= Money::format((float)$m['saldo_acumulado']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
