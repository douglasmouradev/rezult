<?php require __DIR__ . '/../partials/flash.php'; $c = $conta; ?>
<div class="card" style="max-width:480px">
<form method="post" action="<?= $c ? '/contas/'.$c['id'] : '/contas' ?>">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form-group"><label>Nome</label><input class="input" name="nome" value="<?= htmlspecialchars($c['nome'] ?? '') ?>" required></div>
    <div class="form-group"><label>Tipo</label>
        <select name="tipo" class="input">
            <?php foreach (['corrente','poupanca','caixa','cartao','investimento'] as $t): ?>
            <option value="<?= $t ?>" <?= ($c['tipo']??'')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Saldo inicial</label><input class="input" name="saldo_inicial" value="<?= $c['saldo_inicial'] ?? '0' ?>"></div>
    <div class="form-group"><label>Cor</label><input type="color" name="cor" value="<?= $c['cor'] ?? '#10b981' ?>"></div>
    <?php if ($c): ?><label><input type="checkbox" name="ativo" value="1" <?= $c['ativo']?'checked':'' ?>> Ativa</label><?php endif; ?>
    <button type="submit" class="btn-primary" style="margin-top:16px">Salvar</button>
</form>
</div>
