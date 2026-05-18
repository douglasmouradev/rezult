<?php require __DIR__ . '/../partials/flash.php'; ?>
<form method="post" action="/convite/<?= htmlspecialchars($token) ?>" class="auth-form">
    <h2>Convite para equipe</h2>
    <p class="auth-subtitle">Você foi convidado para <strong><?= htmlspecialchars($convite['empresa_nome']) ?></strong> como <?= htmlspecialchars($convite['papel']) ?>.</p>
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <p class="text-muted">E-mail: <?= htmlspecialchars($convite['email']) ?></p>
    <div class="form-group">
        <label for="nome">Seu nome</label>
        <input class="input" id="nome" name="nome" placeholder="Nome completo">
    </div>
    <div class="form-group">
        <label for="senha">Senha (se ainda não tem conta)</label>
        <input class="input" id="senha" type="password" name="senha" minlength="8" placeholder="Mínimo 8 caracteres">
    </div>
    <button type="submit" class="btn-primary">Aceitar convite</button>
</form>
