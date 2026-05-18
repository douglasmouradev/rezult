<?php
use App\Helpers\Money;
require __DIR__ . '/../partials/flash.php';
$r = $resultado;
$acaoLote = $tipo === 'receita' ? 'receber-lote' : 'pagar-lote';
?>
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <span class="stat-label">Total pendente</span>
        <strong class="stat-value"><?= Money::format((float)($resumo['total_pendente'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card stat-warn">
        <span class="stat-label">Atrasado</span>
        <strong class="stat-value"><?= (int)($resumo['qtd_atrasado'] ?? 0) ?> · <?= Money::format((float)($resumo['total_atrasado'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span class="stat-label">Próximos 7 dias</span>
        <strong class="stat-value"><?= Money::format((float)($resumo['total_semana'] ?? 0)) ?></strong>
    </div>
</div>

<div class="page-toolbar">
    <p class="page-subtitle" style="margin:0"><?= $r['total'] ?> título(s)</p>
    <div class="page-actions">
        <a href="<?= htmlspecialchars($criarUrl) ?>" class="btn-primary"><i class="ph ph-plus"></i> Novo</a>
    </div>
</div>

<form class="filters card" method="get">
    <div class="filter-label"><span>Status</span>
        <select name="status" class="input">
            <?php foreach (['pendente','pago','cancelado',''] as $s): ?>
            <option value="<?= $s ?>" <?= ($filtros['status']??'')===$s?'selected':'' ?>><?= $s ? ucfirst($s) : 'Todos' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-label"><span>Vencimento</span>
        <select name="vencimento" class="input">
            <option value="">Todos</option>
            <option value="atrasado" <?= ($_GET['vencimento']??'')==='atrasado'?'selected':'' ?>>Atrasados</option>
            <option value="hoje" <?= ($_GET['vencimento']??'')==='hoje'?'selected':'' ?>>Vence hoje</option>
            <option value="semana" <?= ($_GET['vencimento']??'')==='semana'?'selected':'' ?>>Próximos 7 dias</option>
            <option value="mes" <?= ($_GET['vencimento']??'')==='mes'?'selected':'' ?>>Este mês</option>
        </select>
    </div>
    <div class="filter-label"><span>Fornecedor/Cliente</span>
        <input class="input" name="parceiro" value="<?= htmlspecialchars($filtros['parceiro'] ?? '') ?>" placeholder="Nome...">
    </div>
    <div class="filter-label"><span>Conta</span>
        <select name="conta_id" class="input"><option value="">Todas</option>
            <?php foreach ($contas as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($filtros['conta_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn-primary btn-sm">Filtrar</button>
</form>

<form method="post" action="<?= $basePath ?>/<?= $acaoLote ?>">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="card data-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="check-all"></th>
                        <th>Vencimento</th>
                        <th>Parceiro</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($r['items'])): ?>
                <tr><td colspan="7" class="empty-state">Nenhum título encontrado.</td></tr>
                <?php else: foreach ($r['items'] as $l):
                    $atrasado = $l['status']==='pendente' && !empty($l['data_vencimento']) && $l['data_vencimento'] < date('Y-m-d');
                ?>
                <tr class="<?= $atrasado ? 'row-overdue' : '' ?>">
                    <td><?php if ($l['status']==='pendente'): ?><input type="checkbox" name="ids[]" value="<?= $l['id'] ?>"><?php endif; ?></td>
                    <td><?= $l['data_vencimento'] ? date('d/m/Y', strtotime($l['data_vencimento'])) : '—' ?></td>
                    <td><?= htmlspecialchars($l['parceiro'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($l['descricao']) ?></td>
                    <td class="amount amount-<?= $tipo ?>"><?= Money::format((float)$l['valor']) ?></td>
                    <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
                    <td><a href="/lancamentos/<?= $l['id'] ?>/editar" class="btn-ghost btn-sm">Editar</a></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($r['items'])): ?>
        <div style="padding:16px;display:flex;gap:12px;align-items:center">
            <button type="submit" class="btn-primary btn-sm">
                <?= $tipo === 'receita' ? 'Marcar recebidos' : 'Marcar pagos' ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</form>

<script>
document.getElementById('check-all')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
