<?php require __DIR__ . '/../partials/flash.php'; ?>
<form method="post" action="/recuperar" class="auth-form">
    <h2>Recuperar senha</h2>
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form-group">
        <label>E-mail</label>
        <input class="input" type="email" name="email" required>
    </div>
    <button type="submit" class="btn-primary">Enviar link</button>
    <p class="auth-link"><a href="/login">Voltar ao login</a></p>
</form>
