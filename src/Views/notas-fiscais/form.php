<?php require __DIR__ . '/../partials/flash.php'; $n = $nota; ?>
<div class="card" style="max-width:560px"><form method="post" action="/notas-fiscais">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<?php if ($n): ?><input type="hidden" name="id" value="<?= $n['id'] ?>"><?php endif; ?>
<div class="form-group"><label>Tomador</label><input class="input" name="tomador_nome" required value="<?= htmlspecialchars($n['tomador_nome']??'') ?>"></div>
<div class="form-group"><label>CPF/CNPJ</label><input class="input" name="tomador_documento" required value="<?= htmlspecialchars($n['tomador_documento']??'') ?>"></div>
<div class="form-group"><label>Descrição do serviço</label><textarea class="input" name="descricao_servico" required><?= htmlspecialchars($n['descricao_servico']??'') ?></textarea></div>
<div class="form-group"><label>Valor</label><input class="input" name="valor" required value="<?= $n['valor']??'' ?>"></div>
<button class="btn-primary">Salvar</button></form></div>