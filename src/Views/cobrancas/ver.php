<?php use App\Helpers\Money; require __DIR__ . '/../partials/flash.php'; $c = $cobranca; ?>
<div class="card" style="max-width:640px">
<p><strong>Cliente:</strong> <?= htmlspecialchars($c['cliente_nome']) ?></p>
<?php if ($c['cliente_email']): ?><p><strong>E-mail:</strong> <?= htmlspecialchars($c['cliente_email']) ?></p><?php endif; ?>
<p><strong>Valor:</strong> <?= Money::format((float)$c['valor']) ?></p>
<p><strong>Status:</strong> <?= ucfirst($c['status']) ?></p>
<?php if ($c['codigo_pix']): ?><p><strong>Pix copia e cola:</strong></p><textarea class="input" rows="3" readonly><?= htmlspecialchars($c['codigo_pix']) ?></textarea><?php endif; ?>
<?php if ($c['linha_digitavel']): ?><p><strong>Linha digitável:</strong> <?= htmlspecialchars($c['linha_digitavel']) ?></p><?php endif; ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
<?php if ($c['status']==='rascunho'): ?>
<form method="post" action="/cobrancas/<?= $c['id'] ?>/emitir">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<select name="conta_id" class="input"><?php foreach($contas as $ct): ?><option value="<?= $ct['id'] ?>"><?= htmlspecialchars($ct['nome']) ?></option><?php endforeach; ?></select>
<button class="btn-primary">Emitir cobrança</button></form>
<?php endif; ?>
<?php if ($c['status']==='emitida'): ?>
<form method="post" action="/cobrancas/<?= $c['id'] ?>/pagar"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
<button class="btn-primary">Marcar como paga</button></form>
<?php if ($c['cliente_email']): ?>
<form method="post" action="/cobrancas/<?= $c['id'] ?>/enviar-email"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
<button class="btn-ghost">Enviar por e-mail</button></form>
<?php endif; ?>
<?php endif; ?>
<?php if (!in_array($c['status'], ['paga', 'cancelada'], true)): ?>
<form method="post" action="/cobrancas/<?= $c['id'] ?>/cancelar" data-confirm="Cancelar esta cobrança?"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
<button class="btn-ghost btn-action-danger">Cancelar</button></form>
<?php endif; ?>
</div>
</div>
