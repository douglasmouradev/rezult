<?php require __DIR__ . '/../partials/flash.php'; ?>
<form method="post" action="/login" class="auth-form">
    <h2>Bem-vindo de volta</h2>
    <p class="auth-subtitle">Entre na sua conta para continuar</p>
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form-group">
        <label for="email">E-mail</label>
        <div class="input-wrap">
            <i class="ph ph-envelope"></i>
            <input class="input" id="email" type="email" name="email" required autocomplete="email" placeholder="voce@empresa.com">
        </div>
    </div>
    <div class="form-group">
        <label for="senha">Senha</label>
        <div class="input-wrap">
            <i class="ph ph-lock"></i>
            <input class="input" id="senha" type="password" name="senha" required autocomplete="current-password" placeholder="••••••••">
        </div>
    </div>
    <label class="checkbox-row">
        <input type="checkbox" name="lembrar" value="1">
        Lembrar-me por 30 dias
    </label>
    <button type="submit" class="btn-primary">Entrar</button>
    <p class="auth-link">Não tem conta? <a href="/cadastro">Cadastre-se</a><br><a href="/recuperar">Esqueci minha senha</a></p>
</form>
