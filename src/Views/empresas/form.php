<?php require __DIR__ . '/../partials/flash.php'; $e = $empresa; ?>
<div class="card" style="max-width:480px">
<form method="post" enctype="multipart/form-data" action="<?= $e ? '/empresas/'.$e['id'] : '/empresas' ?>">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form-group"><label>Nome</label><input class="input" name="nome" value="<?= htmlspecialchars($e['nome'] ?? '') ?>" required></div>
    <div class="form-group"><label>CNPJ</label><input class="input" name="cnpj" value="<?= htmlspecialchars($e['cnpj'] ?? '') ?>"></div>
    <div class="form-group"><label>Moeda</label><input class="input" name="moeda" value="<?= $e['moeda'] ?? 'BRL' ?>" maxlength="3"></div>
    <div class="form-group"><label>Logo</label><input type="file" name="logo" accept="image/*"></div>
    <button type="submit" class="btn-primary">Salvar</button>
</form>
</div>
