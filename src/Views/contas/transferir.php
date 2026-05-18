<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="card" style="max-width:480px">
<form method="post" action="/contas/transferir">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form-group"><label>Origem</label>
        <select name="origem_id" class="input" required>
            <?php foreach ($contas as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Destino</label>
        <select name="destino_id" class="input" required>
            <?php foreach ($contas as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Valor</label><input class="input" name="valor" required></div>
    <div class="form-group"><label>Data</label><input class="input" type="date" name="data" value="<?= date('Y-m-d') ?>" required></div>
    <div class="form-group"><label>Descrição</label><input class="input" name="descricao" value="Transferência entre contas"></div>
    <button type="submit" class="btn-primary">Transferir</button>
</form>
</div>
