<?php require __DIR__ . '/../partials/flash.php'; $e = $empresa; ?>
<?php if (!empty($primeiraEmpresa)): ?>
<div class="card mb-2" style="max-width:520px;background:var(--surface-2)">
    <p class="text-muted" style="margin:0">Bem-vindo! Crie sua primeira empresa para acessar o dashboard, lançamentos e relatórios.</p>
</div>
<?php endif; ?>
<div class="card" style="max-width:480px">
<form method="post" enctype="multipart/form-data" action="<?= $e ? '/empresas/'.$e['id'] : '/empresas' ?>">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form-group"><label>Nome</label><input class="input" name="nome" value="<?= htmlspecialchars($e['nome'] ?? '') ?>" required></div>
    <div class="form-group"><label>CNPJ</label><input class="input" name="cnpj" value="<?= htmlspecialchars($e['cnpj'] ?? '') ?>"></div>
    <div class="form-group"><label>Moeda</label><input class="input" name="moeda" value="<?= $e['moeda'] ?? 'BRL' ?>" maxlength="3"></div>
    <div class="form-group"><label>Logo</label><input type="file" name="logo" accept="image/*"></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="submit" class="btn-primary">Salvar</button>
        <?php if (empty($primeiraEmpresa)): ?>
        <a href="/empresas" class="btn btn-ghost">Voltar às empresas</a>
        <?php endif; ?>
    </div>
</form>
</div>
