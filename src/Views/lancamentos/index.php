<?php
use App\Helpers\Money;
$r = $resultado;
?>
<div class="page-toolbar">
    <div>
        <p class="page-subtitle" style="margin:0"><?= $r['total'] ?> lançamento(s) encontrado(s)</p>
    </div>
    <div class="page-actions">
        <a href="/lancamentos/exportar?<?= http_build_query($filtros) ?>" class="btn-ghost btn-sm"><i class="ph ph-download-simple"></i> CSV</a>
        <a href="/lancamentos/importar" class="btn-ghost btn-sm"><i class="ph ph-upload"></i> Importar</a>
        <a href="/lancamentos/criar" class="btn-primary"><i class="ph ph-plus"></i> Novo lançamento</a>
    </div>
</div>

<form class="filters card" method="get">
    <div class="filter-label">
        <span>Busca</span>
        <input class="input" name="busca" placeholder="Descrição..." value="<?= htmlspecialchars($filtros['busca']) ?>">
    </div>
    <div class="filter-label">
        <span>Tipo</span>
        <select name="tipo"><option value="">Todos</option>
            <?php foreach (['receita','despesa'] as $t): ?>
            <option value="<?= $t ?>" <?= $filtros['tipo']===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-label">
        <span>Status</span>
        <select name="status"><option value="">Todos</option>
            <?php foreach (['pago','pendente','cancelado'] as $s): ?>
            <option value="<?= $s ?>" <?= $filtros['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-label">
        <span>Conta</span>
        <select name="conta_id"><option value="">Todas</option>
            <?php foreach ($contas as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filtros['conta_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-label">
        <span>De</span>
        <input class="input" type="date" name="de" value="<?= $filtros['de'] ?>">
    </div>
    <div class="filter-label">
        <span>Até</span>
        <input class="input" type="date" name="ate" value="<?= $filtros['ate'] ?>">
    </div>
    <button type="submit" class="btn-primary btn-sm"><i class="ph ph-funnel"></i> Filtrar</button>
</form>

<div class="card data-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th class="th-actions">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($r['items'])): ?>
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <i class="ph ph-magnifying-glass"></i>
                        <p>Nenhum lançamento encontrado.</p>
                        <a href="/lancamentos/criar" class="btn-primary btn-sm">Criar lançamento</a>
                    </div>
                </td>
            </tr>
            <?php else: foreach ($r['items'] as $l): ?>
            <tr>
                <td class="td-muted"><?= date('d/m/Y', strtotime($l['data_lancamento'])) ?></td>
                <td class="td-desc"><?= htmlspecialchars($l['descricao']) ?></td>
                <td>
                    <?php if (!empty($l['categoria_cor'])): ?>
                    <span class="cat-dot" style="background:<?= htmlspecialchars($l['categoria_cor']) ?>"></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($l['categoria_nome'] ?? '—') ?>
                </td>
                <td class="amount amount-<?= $l['tipo'] ?>"><?= Money::format((float)$l['valor']) ?></td>
                <td>
                    <form method="post" action="/lancamentos/<?= $l['id'] ?>/status" class="toggle-status-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <button type="submit" class="badge badge-<?= $l['status'] ?> badge-toggle toggle-status"><?= ucfirst($l['status']) ?></button>
                    </form>
                </td>
                <td>
                    <div class="row-actions">
                        <a href="/lancamentos/<?= $l['id'] ?>/editar" class="btn-ghost btn-sm btn-with-icon btn-action" title="Editar lançamento">
                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                            <span class="btn-label">Editar</span>
                        </a>
                        <form method="post" action="/lancamentos/<?= $l['id'] ?>/duplicar" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <button type="submit" class="btn-ghost btn-sm btn-with-icon btn-action" title="Duplicar lançamento">
                                <i class="ph ph-copy" aria-hidden="true"></i>
                                <span class="btn-label">Copiar</span>
                            </button>
                        </form>
                        <?php if (!empty($podeGerenciar)): ?>
                        <form method="post" action="/lancamentos/<?= $l['id'] ?>/excluir" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <button type="submit" class="btn-ghost btn-sm btn-with-icon btn-action btn-action-danger" title="Excluir lançamento" data-confirm="Excluir este lançamento?">
                                <i class="ph ph-trash" aria-hidden="true"></i>
                                <span class="btn-label">Excluir</span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($r['pages'] > 1): ?>
    <nav class="pagination" aria-label="Paginação">
        <?php for ($p = 1; $p <= min($r['pages'], 10); $p++): ?>
        <a href="?<?= http_build_query(array_merge($filtros, ['page'=>$p])) ?>" class="btn-ghost btn-sm <?= $p==$r['page']?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
