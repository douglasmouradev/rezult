<?php require __DIR__ . '/../partials/flash.php'; ?>
<form method="post" action="/cadastro" class="auth-form">
    <h2>Criar conta</h2>
    <p class="auth-subtitle">Comece a organizar suas finanças em minutos</p>
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form-group">
        <label for="nome">Nome completo</label>
        <input class="input" id="nome" name="nome" required placeholder="Seu nome">
    </div>
    <div class="form-group">
        <label for="email">E-mail</label>
        <div class="input-wrap">
            <i class="ph ph-envelope"></i>
            <input class="input" id="email" type="email" name="email" required placeholder="voce@empresa.com">
        </div>
    </div>
    <div class="form-group">
        <label for="senha">Senha</label>
        <div class="input-wrap">
            <i class="ph ph-lock"></i>
            <input class="input" id="senha" type="password" name="senha" required minlength="8" placeholder="Mínimo 8 caracteres">
        </div>
    </div>
    <div class="form-group">
        <label for="senha2">Confirmar senha</label>
        <input class="input" id="senha2" type="password" name="senha_confirmacao" required placeholder="Repita a senha">
    </div>
    <label class="checkbox-row">
        <input type="checkbox" name="aceite_termos" value="1" required>
        Li e aceito os <a href="/termos" target="_blank">Termos de Uso</a>
    </label>
    <label class="checkbox-row">
        <input type="checkbox" name="aceite_privacidade" value="1" required>
        Li a <a href="/privacidade" target="_blank">Política de Privacidade</a> (LGPD)
    </label>
    <label class="checkbox-row">
        <input type="checkbox" name="marketing_optin" value="1">
        Aceito receber comunicações opcionais (marketing)
    </label>
    <button type="submit" class="btn-primary">Criar conta</button>
    <p class="auth-link">Já tem conta? <a href="/login">Entrar</a></p>
</form>
