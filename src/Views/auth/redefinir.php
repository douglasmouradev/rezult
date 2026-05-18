<?php require __DIR__ . '/../partials/flash.php'; ?>
<form method="post" action="/redefinir" class="auth-form">
    <h2>Nova senha</h2>
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
    <div class="form-group">
        <label>Nova senha</label>
        <input class="input" type="password" name="senha" required minlength="8">
    </div>
    <div class="form-group">
        <label>Confirmar senha</label>
        <input class="input" type="password" name="senha_confirmacao" required minlength="8">
    </div>
    <button type="submit" class="btn-primary">Salvar</button>
</form>
