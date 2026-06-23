<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="page-header"><h1>Contatos</h1></div>
<form method="post" action="/contatos" class="card mb-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="grid-3">
        <input class="input" name="nome" placeholder="Nome" required>
        <input class="input" name="documento" placeholder="CPF/CNPJ">
        <input class="input" name="email" type="email" placeholder="E-mail">
    </div>
    <div class="grid-3" style="margin-top:8px">
        <input class="input" name="telefone" placeholder="Telefone">
        <select name="tipo" class="input">
            <option value="cliente">Cliente</option>
            <option value="fornecedor">Fornecedor</option>
            <option value="ambos">Cliente e fornecedor</option>
        </select>
        <button class="btn btn-primary">Adicionar</button>
    </div>
</form>
<div class="card">
<table class="data-table">
<thead><tr><th>Nome</th><th>Documento</th><th>E-mail</th><th>Telefone</th><th>Tipo</th><th class="th-actions">Ações</th></tr></thead>
<tbody><?php if (empty($contatos)): ?>
<tr><td colspan="6"><div class="empty-state" style="padding:32px"><i class="ph ph-address-book"></i><p>Nenhum contato cadastrado.</p></div></td></tr>
<?php else: foreach ($contatos as $c): ?>
<tr>
    <td><?= htmlspecialchars($c['nome']) ?></td>
    <td><?= htmlspecialchars($c['documento'] ?? '') ?></td>
    <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
    <td><?= htmlspecialchars($c['telefone'] ?? '') ?></td>
    <td><span class="badge"><?= ucfirst($c['tipo']) ?></span></td>
    <td>
        <div class="row-actions">
            <form method="post" action="/contatos" class="inline-form" style="display:inline-flex;gap:4px;flex-wrap:wrap">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input class="input btn-sm" name="nome" value="<?= htmlspecialchars($c['nome']) ?>" required>
                <input class="input btn-sm" name="documento" value="<?= htmlspecialchars($c['documento'] ?? '') ?>" placeholder="Documento">
                <input class="input btn-sm" name="email" value="<?= htmlspecialchars($c['email'] ?? '') ?>" placeholder="E-mail">
                <input class="input btn-sm" name="telefone" value="<?= htmlspecialchars($c['telefone'] ?? '') ?>" placeholder="Telefone">
                <select name="tipo" class="input btn-sm">
                    <option value="cliente" <?= $c['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="fornecedor" <?= $c['tipo'] === 'fornecedor' ? 'selected' : '' ?>>Fornecedor</option>
                    <option value="ambos" <?= $c['tipo'] === 'ambos' ? 'selected' : '' ?>>Ambos</option>
                </select>
                <button type="submit" class="btn-ghost btn-sm" title="Salvar">Salvar</button>
            </form>
            <form method="post" action="/contatos/<?= (int) $c['id'] ?>/excluir" class="inline-form" data-confirm="Remover este contato?">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn-ghost btn-sm btn-action-danger">Excluir</button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; endif; ?></tbody>
</table>
</div>
