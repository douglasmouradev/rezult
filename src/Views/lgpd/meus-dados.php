<div class="page-header">
    <h1>Meus dados (LGPD)</h1>
    <p class="text-muted">Exercite seus direitos de titular de dados</p>
</div>

<div class="grid-2">
    <div class="card">
        <h3>Portabilidade</h3>
        <p>Baixe uma cópia dos seus dados pessoais em JSON.</p>
        <a href="/privacidade/exportar" class="btn btn-primary">Exportar meus dados</a>
        <?php if (!empty($podeGerenciar)): ?>
        <p class="mt-2"><a href="/privacidade/exportar-empresa" class="btn btn-secondary">Exportar dados da empresa</a></p>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Retificação</h3>
        <form method="post" action="/privacidade/retificar">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <label>Nome</label>
            <input class="input" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
            <button type="submit" class="btn btn-primary mt-2">Atualizar dados</button>
        </form>
    </div>
    <div class="card card-danger">
        <h3>Exclusão de conta</h3>
        <p>Confirmação imediata após solicitação. Dados serão anonimizados.</p>
        <form method="post" action="/privacidade/excluir" class="mb-2">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button type="submit" class="btn btn-secondary">Registrar solicitação</button>
        </form>
        <form method="post" action="/privacidade/confirmar-exclusao" onsubmit="return confirm('Esta ação é irreversível. Continuar?');">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <label>Digite <strong>EXCLUIR</strong> para confirmar</label>
            <input class="input" name="confirmar" required placeholder="EXCLUIR">
            <label class="mt-2">Senha atual</label>
            <input class="input" type="password" name="senha" required placeholder="Confirme sua identidade">
            <button type="submit" class="btn btn-danger mt-2">Confirmar exclusão</button>
        </form>
    </div>
</div>
