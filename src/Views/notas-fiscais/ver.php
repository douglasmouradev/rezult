<?php use App\Helpers\Money; require __DIR__ . '/../partials/flash.php'; $n = $nota; ?>
<div class="card"><p><strong>Tomador:</strong> <?= htmlspecialchars($n['tomador_nome']) ?> (<?= htmlspecialchars($n['tomador_documento']) ?>)</p>
<p><strong>Valor:</strong> <?= Money::format((float)$n['valor']) ?></p>
<p><strong>Status:</strong> <?= ucfirst($n['status']) ?></p>
<?php if ($n['numero']): ?><p><strong>Número:</strong> <?= htmlspecialchars($n['numero']) ?> · Verificação: <?= htmlspecialchars($n['codigo_verificacao']) ?></p><?php endif; ?>
<?php if ($n['status']==='rascunho'): ?>
<form method="post" action="/notas-fiscais/<?= $n['id'] ?>/emitir"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
<button class="btn-primary">Emitir NFS-e</button></form><?php endif; ?></div>