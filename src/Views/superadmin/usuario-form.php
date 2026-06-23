<div class="page-header">
    <h1>Novo usuário</h1>
    <p class="text-muted">Criar conta manualmente na plataforma</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="card" style="max-width:520px">
    <form method="post" action="/superadmin/usuarios">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <label>Nome completo</label>
        <input class="input" name="nome" required>
        <label class="mt-2">E-mail</label>
        <input class="input" type="email" name="email" required>
        <label class="mt-2">Senha</label>
        <input class="input" type="password" name="senha" minlength="8" required>
        <label class="mt-2">Confirmar senha</label>
        <input class="input" type="password" name="senha_confirmacao" minlength="8" required>
        <label class="mt-2 checkbox-label">
            <input type="checkbox" name="email_verificado" value="1" checked>
            E-mail já verificado
        </label>
        <label class="mt-2 checkbox-label">
            <input type="checkbox" name="is_superadmin" value="1">
            Conceder superadmin
        </label>
        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="submit" class="btn btn-primary">Criar usuário</button>
            <a href="/superadmin/usuarios" class="btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
