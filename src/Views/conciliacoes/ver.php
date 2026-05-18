<?php use App\Helpers\Money; require __DIR__ . '/../partials/flash.php'; ?>
<div class="card"><p>Conciliação #<?= $conciliacao['id'] ?> — <?= (int)$conciliacao['conciliados'] ?>/<?= (int)$conciliacao['total_itens'] ?> conciliados</p>
<table><thead><tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Status</th><th>Lançamento</th></tr></thead><tbody>
<?php foreach ($itens as $i): ?>
<tr><td><?= date('d/m/Y', strtotime($i['data_movimento'])) ?></td><td><?= htmlspecialchars($i['descricao']) ?></td>
<td><?= Money::format((float)$i['valor']) ?></td><td><?= $i['status'] ?></td>
<td><?php if ($i['status']==='pendente'): ?>
<form method="post" action="/conciliacoes/<?= $conciliacao['id'] ?>/conciliar"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
<input type="hidden" name="item_id" value="<?= $i['id'] ?>">
<select name="lancamento_id"><?php foreach($lancamentos as $l): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['descricao']) ?> — <?= Money::format((float)$l['valor']) ?></option><?php endforeach; ?></select>
<button class="btn-sm btn-primary">Conciliar</button></form>
<?php else: ?><?= htmlspecialchars($i['lancamento_descricao'] ?? '—') ?><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>