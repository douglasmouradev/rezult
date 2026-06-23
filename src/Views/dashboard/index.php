<?php
use App\Helpers\Money;
$d = $dados;
?>
<?php if (!empty($showOnboarding)): ?>
<div class="card onboarding-card mb-2">
    <h3>Bem-vindo ao Rezult</h3>
    <p>1. Cadastre contas · 2. Categorias · 3. Primeiro lançamento · 4. Veja o dashboard</p>
    <form method="post" action="/onboarding/concluir"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <button class="btn btn-primary btn-sm">Entendi, começar</button></form>
</div>
<?php endif; ?>
<?php $resultadoHint = $d['resultado_mes'] >= 0 ? '↑ Lucro no período' : '↓ Prejuízo no período'; ?>
<div class="stats-grid">
    <?php foreach (
        [
            ['saldo', 'Saldo total', $d['saldo_total'], 'wallet', null],
            ['receita', 'Receitas do mês', $d['receitas_mes'], 'arrow-up-right', null],
            ['despesa', 'Despesas do mês', $d['despesas_mes'], 'arrow-down-right', null],
            ['resultado', 'Resultado', $d['resultado_mes'], 'scales', $resultadoHint],
            ['inadimplencia', 'Inadimplência', $d['inadimplencia_valor'] ?? 0, 'warning', ($d['inadimplencia_qtd'] ?? 0) . ' título(s) · ' . ($d['inadimplencia_pct'] ?? 0) . '%'],
        ] as [$variant, $label, $value, $icon, $hint]
    ) {
        require __DIR__ . '/../partials/stat-card.php';
    } ?>
</div>

<div class="grid-3" style="margin-bottom:20px">
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Fluxo de caixa</h3>
                <p class="card-desc">Últimos 12 meses</p>
            </div>
        </div>
        <div id="chart-fluxo" class="chart-box"></div>
    </div>
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Despesas</h3>
                <p class="card-desc">Por categoria neste mês</p>
            </div>
        </div>
        <div id="chart-categorias" class="chart-box"></div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:20px">
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Receitas vs despesas</h3>
                <p class="card-desc">Comparativo semestral</p>
            </div>
        </div>
        <div id="chart-comparativo" class="chart-box"></div>
    </div>
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Próximos vencimentos</h3>
                <p class="card-desc">Nos próximos 7 dias</p>
            </div>
            <a href="/lancamentos?status=pendente" class="btn-ghost btn-sm">Ver todos</a>
        </div>
        <ul class="alert-list">
            <?php if (empty($d['vencendo'])): ?>
            <li class="empty-state" style="padding:24px">
                <i class="ph ph-check-circle"></i>
                <p>Nada vencendo por aqui.</p>
            </li>
            <?php else: foreach ($d['vencendo'] as $v): ?>
            <li class="alert-item">
                <div>
                    <strong><?= htmlspecialchars($v['descricao']) ?></strong>
                    <span class="td-muted"><?= htmlspecialchars($v['conta_nome'] ?? '') ?></span>
                </div>
                <span class="badge badge-pendente"><?= Money::format((float)$v['valor']) ?> · <?= date('d/m', strtotime($v['data_vencimento'])) ?></span>
            </li>
            <?php endforeach; endif; ?>
        </ul>
    </div>
</div>

<div class="card data-card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Últimos lançamentos</h3>
            <p class="card-desc">Movimentações recentes</p>
        </div>
        <a href="/lancamentos" class="btn-ghost btn-sm">Ver todos <i class="ph ph-arrow-right"></i></a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($d['ultimos_lancamentos'])): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="ph ph-receipt"></i><p>Nenhum lançamento ainda.</p><a href="/lancamentos/criar" class="btn-primary btn-sm">Criar primeiro</a></div></td></tr>
            <?php else: foreach ($d['ultimos_lancamentos'] as $l): ?>
            <tr>
                <td class="td-muted"><?= date('d/m/Y', strtotime($l['data_lancamento'])) ?></td>
                <td class="td-desc"><?= htmlspecialchars($l['descricao']) ?></td>
                <td><span class="badge badge-<?= $l['tipo'] ?>"><?= ucfirst($l['tipo']) ?></span></td>
                <td class="amount amount-<?= $l['tipo'] ?>"><?= Money::format((float)$l['valor']) ?></td>
                <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const chartOpts = {
    chart: { background: 'transparent', foreColor: '#6b7280', fontFamily: 'IBM Plex Sans, sans-serif', toolbar: { show: false }, animations: { enabled: true, speed: 400 } },
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
    tooltip: { theme: 'light' },
  };

  const fluxo = <?= json_encode($d['fluxo_12m']) ?>;
  new ApexCharts(document.querySelector('#chart-fluxo'), {
    ...chartOpts,
    chart: { ...chartOpts.chart, type: 'area', height: 280 },
    series: [{ name: 'Saldo', data: fluxo.map(r => parseFloat(r.saldo)) }],
    xaxis: { categories: fluxo.map(r => r.ym), axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { formatter: v => 'R$ ' + (v/1000).toFixed(0) + 'k' } },
    colors: ['#475569'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0.02 } },
    stroke: { curve: 'smooth', width: 2.5 },
    dataLabels: { enabled: false },
  }).render();

  const cats = <?= json_encode($d['despesas_categoria']) ?>;
  if (cats.length) {
    new ApexCharts(document.querySelector('#chart-categorias'), {
      chart: { type: 'donut', height: 280, background: 'transparent' },
      series: cats.map(c => parseFloat(c.total)),
      labels: cats.map(c => c.nome),
      colors: cats.map(c => c.cor || '#6366f1'),
      legend: { position: 'bottom', labels: { colors: '#6b7280' }, fontSize: '12px' },
      plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Total', color: '#6b7280', formatter: () => '' } } } } },
      stroke: { width: 0 },
    }).render();
  }

  const comp = <?= json_encode($d['comparativo_mensal']) ?>;
  new ApexCharts(document.querySelector('#chart-comparativo'), {
    ...chartOpts,
    chart: { ...chartOpts.chart, type: 'bar', height: 280 },
    series: [
      { name: 'Receitas', data: comp.map(r => parseFloat(r.receitas)) },
      { name: 'Despesas', data: comp.map(r => parseFloat(r.despesas)) }
    ],
    xaxis: { categories: comp.map(r => r.ym) },
    colors: ['#047857', '#b91c1c'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '52%' } },
    legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#6b7280' } },
  }).render();
});
</script>
