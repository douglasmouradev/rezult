<?php
use App\Helpers\Money;
$positivo = $dre['resultado'] >= 0;
$res = Money::parts($dre['resultado']);
$rec = Money::parts($dre['total_receitas']);
$desp = Money::parts($dre['total_despesas']);
?>
<form class="filters card" method="get">
    <div class="filter-label">
        <span>De</span>
        <input type="date" name="de" class="input" value="<?= $periodo['de'] ?>">
    </div>
    <div class="filter-label">
        <span>Até</span>
        <input type="date" name="ate" class="input" value="<?= $periodo['ate'] ?>">
    </div>
    <button type="submit" class="btn-primary btn-sm"><i class="ph ph-funnel"></i> Atualizar</button>
    <a href="?de=<?= $periodo['de'] ?>&ate=<?= $periodo['ate'] ?>&formato=xlsx" class="btn-ghost btn-sm"><i class="ph ph-file-xls"></i> Excel</a>
    <a href="?de=<?= $periodo['de'] ?>&ate=<?= $periodo['ate'] ?>&formato=pdf" class="btn-ghost btn-sm"><i class="ph ph-file-pdf"></i> PDF</a>
</form>

<div class="dre-columns">
    <div class="card dre-panel">
        <div class="dre-panel-header receitas">
            <span class="dre-panel-icon"><i class="ph ph-trend-up"></i></span>
            <div>
                <div class="dre-panel-title">Receitas</div>
                <div class="dre-panel-sub"><?= count($dre['receitas']) ?> categoria(s)</div>
            </div>
        </div>
        <div class="dre-lines">
            <?php if (empty($dre['receitas'])): ?>
            <p class="dre-empty">Nenhuma receita no período.</p>
            <?php else: foreach ($dre['receitas'] as $r): ?>
            <div class="dre-line">
                <span class="dre-line-name"><?= htmlspecialchars($r['nome']) ?></span>
                <span class="dre-line-value receita"><?= Money::format((float)$r['total']) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="dre-panel-footer receitas">
            <span>Total receitas</span>
            <span class="total"><?= Money::format($dre['total_receitas']) ?></span>
        </div>
    </div>

    <div class="card dre-panel">
        <div class="dre-panel-header despesas">
            <span class="dre-panel-icon"><i class="ph ph-trend-down"></i></span>
            <div>
                <div class="dre-panel-title">Despesas</div>
                <div class="dre-panel-sub"><?= count($dre['despesas']) ?> categoria(s)</div>
            </div>
        </div>
        <div class="dre-lines">
            <?php if (empty($dre['despesas'])): ?>
            <p class="dre-empty">Nenhuma despesa no período.</p>
            <?php else: foreach ($dre['despesas'] as $d): ?>
            <div class="dre-line">
                <span class="dre-line-name"><?= htmlspecialchars($d['nome']) ?></span>
                <span class="dre-line-value despesa"><?= Money::format((float)$d['total']) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="dre-panel-footer despesas">
            <span>Total despesas</span>
            <span class="total"><?= Money::format($dre['total_despesas']) ?></span>
        </div>
    </div>
</div>

<div class="card dre-result <?= $positivo ? 'positive' : 'negative' ?>">
    <div class="dre-result-inner">
        <div class="dre-equation-block">
            <span class="dre-equation-label">Receitas</span>
            <span class="dre-equation-value receitas">
                <span class="currency"><?= $rec['symbol'] ?></span> <?= $rec['amount'] ?>
            </span>
        </div>
        <span class="dre-equation-op" aria-hidden="true">−</span>
        <div class="dre-equation-block">
            <span class="dre-equation-label">Despesas</span>
            <span class="dre-equation-value despesas">
                <span class="currency"><?= $desp['symbol'] ?></span> <?= $desp['amount'] ?>
            </span>
        </div>
        <span class="dre-equation-op" aria-hidden="true">=</span>
        <div class="dre-result-hero">
            <span class="dre-equation-label">Resultado líquido</span>
            <div class="dre-result-amount">
                <span class="currency"><?= $res['symbol'] ?></span>
                <span><?= $res['amount'] ?></span>
            </div>
            <span class="dre-result-badge">
                <i class="ph ph-<?= $positivo ? 'trend-up' : 'trend-down' ?>"></i>
                <?= $positivo ? 'Lucro no período' : 'Prejuízo no período' ?>
            </span>
        </div>
    </div>
</div>
