<?php use App\Helpers\Money; ?>
<h1>DRE Simplificado</h1>
<p>Período: <?= $periodo['de'] ?> a <?= $periodo['ate'] ?></p>
<h2>Receitas: <?= Money::format($dre['total_receitas']) ?></h2>
<h2>Despesas: <?= Money::format($dre['total_despesas']) ?></h2>
<h2>Resultado: <?= Money::format($dre['resultado']) ?></h2>
