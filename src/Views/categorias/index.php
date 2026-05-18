<div class="grid-2">
    <div class="card">
        <div class="card-header" style="padding:0;margin-bottom:20px">
            <div>
                <h3 class="card-title">Nova categoria</h3>
                <p class="card-desc">Organize receitas e despesas</p>
            </div>
        </div>
        <form method="post" action="/categorias">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="form-group">
                <label>Nome</label>
                <input class="input" name="nome" required placeholder="Ex: Marketing">
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" class="input">
                    <option value="receita">Receita</option>
                    <option value="despesa">Despesa</option>
                </select>
            </div>
            <div class="form-group" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label>Cor</label>
                    <input type="color" name="cor" value="#6366f1" style="height:42px;padding:4px">
                </div>
                <div>
                    <label>Ícone</label>
                    <input class="input" name="icone" placeholder="tag">
                </div>
            </div>
            <button type="submit" class="btn-primary">Adicionar</button>
        </form>
    </div>
    <div class="card data-card" style="padding:0">
        <div class="card-header" style="padding:20px 24px 0">
            <h3 class="card-title">Suas categorias</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Categoria</th><th>Tipo</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($categorias)): ?>
                <tr><td colspan="3"><div class="empty-state" style="padding:32px"><i class="ph ph-tag"></i><p>Nenhuma categoria.</p></div></td></tr>
                <?php else: foreach ($categorias as $cat): ?>
                <tr>
                    <td>
                        <span class="cat-dot" style="background:<?= htmlspecialchars($cat['cor']) ?>"></span>
                        <?= htmlspecialchars($cat['nome']) ?>
                    </td>
                    <td><span class="badge badge-<?= $cat['tipo'] ?>"><?= ucfirst($cat['tipo']) ?></span></td>
                    <td>
                        <form method="post" action="/categorias/<?= $cat['id'] ?>/excluir" onsubmit="return confirm('Excluir categoria?')">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <button type="submit" class="btn-ghost btn-sm btn-with-icon btn-action btn-action-danger" title="Excluir categoria" data-confirm="Excluir esta categoria?">
                                <i class="ph ph-trash" aria-hidden="true"></i><span class="btn-label">Excluir</span>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
