<?php require __DIR__ . '/../partials/flash.php'; ?>
<form method="post" action="/convite/<?= htmlspecialchars($token) ?>" class="auth-form">
    <h2>Convite para equipe</h2>
    <p class="auth-subtitle">Você foi convidado para <strong><?= htmlspecialchars($convite['empresa_nome']) ?></strong> como <?= htmlspecialchars($convite['papel']) ?>.</p>
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <p class="text-muted">E-mail: <?= htmlspecialchars($convite['email']) ?></p>
    <?php if (empty($usuarioExiste)): ?>
    <div class="form-group">
        <label for="nome">Seu nome</label>
        <input class="input" id="nome" name="nome" required placeholder="Nome completo">
    </div>
    <div class="form-group">
        <label for="senha">Criar senha</label>
        <input class="input" id="senha" type="password" name="senha" required minlength="8" placeholder="Mínimo 8 caracteres, com maiúscula, número">
    </div>
    <?php else: ?>
    <p class="auth-subtitle">Já existe uma conta com este e-mail. Informe sua senha para aceitar o convite.</p>
    <div class="form-group">
        <label for="senha">Senha da sua conta</label>
        <input class="input" id="senha" type="password" name="senha" required placeholder="Sua senha atual">
    </div>
    <?php endif; ?>
    <button type="submit" class="btn-primary">Aceitar convite</button>
</form>
