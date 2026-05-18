<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="grid-2">
<div class="card"><h3>Importar extrato (CSV)</h3>
<p class="page-subtitle">Colunas: data;descrição;valor;tipo (credito/debito)</p>
<form method="post" action="/conciliacoes/importar" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<select name="conta_id" class="input" required><?php foreach($contas as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?></select>
<input type="file" name="csv" accept=".csv" required style="margin:12px 0">
<button class="btn-primary">Importar</button></form></div>
<div class="card"><h3>Histórico</h3>
<table><thead><tr><th>Conta</th><th>Itens</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($lista as $c): ?>
<tr><td><?= htmlspecialchars($c['conta_nome']) ?></td><td><?= (int)$c['conciliados'] ?>/<?= (int)$c['total_itens'] ?></td>
<td><?= ucfirst($c['status']) ?></td><td><a href="/conciliacoes/<?= $c['id'] ?>">Abrir</a></td></tr>
<?php endforeach; ?></tbody></table></div></div>