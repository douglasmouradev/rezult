<div class="page-header"><h1>Meu perfil</h1></div>
<div class="grid-2">
    <div class="card">
        <h3>Dados pessoais</h3>
        <form method="post" action="/perfil" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <label>Nome</label>
            <input class="input" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
            <label class="mt-2">Avatar</label>
            <input type="file" name="avatar" accept="image/*" class="input">
            <label class="mt-2 checkbox-label">
                <input type="checkbox" name="ia_consentimento" value="1" <?= !empty($usuario['ia_consentimento']) ? 'checked' : '' ?>>
                Permitir uso do assistente de IA (OpenAI) com meus dados financeiros
            </label>
            <p class="text-muted" style="font-size:0.85rem;margin-top:4px">Necessário para categorização e insights automáticos. Você pode revogar a qualquer momento.</p>
            <button type="submit" class="btn btn-primary mt-2">Salvar</button>
        </form>
    </div>
    <div class="card">
        <h3>Alterar senha</h3>
        <form method="post" action="/perfil/senha">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <label>Senha atual</label>
            <input class="input" type="password" name="senha_atual" required>
            <label class="mt-2">Nova senha</label>
            <input class="input" type="password" name="senha" minlength="8" required>
            <label class="mt-2">Confirmar</label>
            <input class="input" type="password" name="senha_confirmacao" minlength="8" required>
            <button type="submit" class="btn btn-primary mt-2">Atualizar senha</button>
        </form>
    </div>
</div>
