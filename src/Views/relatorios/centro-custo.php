<?php use App\Helpers\Money; ?>
<form class="filters card" method="get">
    <div class="filter-label">
        <span>De</span>
        <input type="date" name="de" class="input" value="<?= $periodo['de'] ?>">
    </div>
    <div class="filter-label">
        <span>Até</span>
        <input type="date" name="ate" class="input" value="<?= $periodo['ate'] ?>">
    </div>
    <div class="filter-label">
        <span>Tipo</span>
        <select name="tipo" class="input">
            <?php foreach (['despesa', 'receita'] as $t): ?>
            <option value="<?= $t ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn-primary btn-sm"><i class="ph ph-funnel"></i> Atualizar</button>
    <a href="?de=<?= $periodo['de'] ?>&ate=<?= $periodo['ate'] ?>&tipo=<?= $tipo ?>&formato=xlsx" class="btn-ghost btn-sm"><i class="ph ph-file-xls"></i> Excel</a>
</form>

<div class="card data-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Centro de custo</th>
                    <th>Código</th>
                    <th>Qtd</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($dados)): ?>
            <tr><td colspan="4"><div class="empty-state"><p>Nenhum lançamento no período.</p></div></td></tr>
            <?php else: foreach ($dados as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['nome']) ?></td>
                <td class="td-muted"><?= htmlspecialchars($d['codigo'] ?? '—') ?></td>
                <td><?= (int) $d['qtd'] ?></td>
                <td class="amount"><?= Money::format((float) $d['total']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
