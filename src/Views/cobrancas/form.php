<?php require __DIR__ . '/../partials/flash.php'; $c = $cobranca; ?>
<div class="card" style="max-width:560px"><form method="post" action="/cobrancas">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<?php if ($c): ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><?php endif; ?>
<div class="form-group"><label>Cliente</label><input class="input" name="cliente_nome" value="<?= htmlspecialchars($c['cliente_nome']??'') ?>" required></div>
<div class="form-group"><label>E-mail</label><input class="input" name="cliente_email" type="email" value="<?= htmlspecialchars($c['cliente_email']??'') ?>"></div>
<div class="form-group"><label>Descrição</label><input class="input" name="descricao" value="<?= htmlspecialchars($c['descricao']??'') ?>" required></div>
<div class="form-group"><label>Valor</label><input class="input" name="valor" value="<?= $c['valor']??'' ?>" required></div>
<div class="form-group"><label>Vencimento</label><input class="input" type="date" name="vencimento" value="<?= $c['vencimento']??date('Y-m-d', strtotime('+7 days')) ?>" required></div>
<div class="form-group"><label>Tipo</label><select name="tipo" class="input"><option value="pix">Pix</option><option value="boleto">Boleto</option></select></div>
<button class="btn-primary">Salvar</button></form></div>