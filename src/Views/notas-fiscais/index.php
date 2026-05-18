<?php use App\Helpers\Money; require __DIR__ . '/../partials/flash.php'; ?>
<div class="page-toolbar"><a href="/notas-fiscais/criar" class="btn-primary">Nova NFS-e</a></div>
<div class="card data-card"><table><thead><tr><th>Tomador</th><th>Valor</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($resultado['items'] as $n): ?>
<tr><td><?= htmlspecialchars($n['tomador_nome']) ?></td><td><?= Money::format((float)$n['valor']) ?></td>
<td><?= ucfirst($n['status']) ?></td><td><a href="/notas-fiscais/<?= $n['id'] ?>">Ver</a></td></tr>
<?php endforeach; ?></tbody></table></div>