<?php require __DIR__ . '/../partials/flash.php'; $l = $lancamento; ?>
<div class="card" style="max-width:640px">
<form method="post" action="/lancamentos" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <?php if ($l): ?><input type="hidden" name="id" value="<?= $l['id'] ?>"><?php endif; ?>
    <div class="form-group"><label>Descrição</label><input class="input" name="descricao" value="<?= htmlspecialchars($l['descricao'] ?? '') ?>" required></div>
    <div class="form-group"><label>Fornecedor / Cliente</label><input class="input" name="parceiro" value="<?= htmlspecialchars($l['parceiro'] ?? '') ?>" placeholder="Nome do parceiro"></div>
    <div class="form-group"><label>Tipo</label>
        <select name="tipo" class="input">
            <?php foreach (['receita','despesa'] as $t): ?>
            <option value="<?= $t ?>" <?= ($l['tipo']??$_GET['tipo']??'')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Valor</label><input class="input" name="valor" value="<?= $l['valor'] ?? '' ?>" required></div>
    <div class="form-group"><label>Conta</label>
        <select name="conta_id" class="input" required>
            <?php foreach ($contas as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($l['conta_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Categoria</label>
        <select name="categoria_id" class="input">
            <option value="">—</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($l['categoria_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Meta</label>
        <select name="meta_id" class="input">
            <option value="">—</option>
            <?php foreach ($metas as $m): ?>
            <option value="<?= $m['id'] ?>" <?= ($l['meta_id']??'')==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['descricao']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Data</label><input class="input" type="date" name="data_lancamento" value="<?= $l['data_lancamento'] ?? date('Y-m-d') ?>" required></div>
    <div class="form-group"><label>Vencimento</label><input class="input" type="date" name="data_vencimento" value="<?= $l['data_vencimento'] ?? '' ?>"></div>
    <div class="form-group"><label>Status</label>
        <select name="status" class="input">
            <?php foreach (['pendente','pago','cancelado'] as $s): ?>
            <option value="<?= $s ?>" <?= ($l['status']??'pendente')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Tags (vírgula)</label>
        <input class="input" name="tags" value="<?= isset($l['tags']) ? htmlspecialchars(implode(', ', json_decode($l['tags'], true) ?: [])) : '' ?>">
    </div>
    <label style="display:flex;gap:8px;margin-bottom:12px"><input type="checkbox" name="recorrente" value="1" <?= !empty($l['recorrente'])?'checked':'' ?>> Recorrente</label>
    <select name="frequencia" class="input" style="margin-bottom:16px">
        <option value="mensal">Mensal</option><option value="semanal">Semanal</option><option value="anual">Anual</option>
    </select>
    <div class="form-group"><label>Anexo</label><input type="file" name="anexo" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
    <button type="submit" class="btn-primary">Salvar</button>
    <a href="/lancamentos" class="btn-ghost" style="margin-left:8px">Cancelar</a>
</form>
</div>
