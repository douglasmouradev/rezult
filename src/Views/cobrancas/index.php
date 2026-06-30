<?php use App\Helpers\Money; require __DIR__ . '/../partials/flash.php'; ?>
<div class="card mb-2" style="background:#fff8e6;border-color:#f59e0b;font-size:0.9rem">
    <p style="margin:0">Pix/boleto são <strong>simulados</strong> até configurar gateway em Integrações. Não use para cobrança real sem gateway ativo.</p>
</div>
<div class="page-toolbar"><a href="/cobrancas/criar" class="btn-primary"><i class="ph ph-plus"></i> Nova cobrança</a></div>
<div class="card data-card"><table><thead><tr><th>Cliente</th><th>Valor</th><th>Vencimento</th><th>Tipo</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($resultado['items'] as $c): ?>
<tr><td><?= htmlspecialchars($c['cliente_nome']) ?></td><td><?= Money::format((float)$c['valor']) ?></td>
<td><?= date('d/m/Y', strtotime($c['vencimento'])) ?></td><td><?= strtoupper($c['tipo']) ?></td>
<td><span class="badge badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
<td><a href="/cobrancas/<?= $c['id'] ?>" class="btn-ghost btn-sm">Ver</a></td></tr>
<?php endforeach; ?></tbody></table></div>