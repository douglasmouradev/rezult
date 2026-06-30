<?php use App\Helpers\Money; require __DIR__ . '/../partials/flash.php'; ?>
<?php if (($modoCobranca ?? 'simulacao') === 'simulacao'): ?>
<div class="card mb-2" style="background:#fff8e6;border-color:#f59e0b;font-size:0.9rem">
    <p style="margin:0">Pix/boleto são <strong>simulados</strong> até configurar o gateway Asaas em Integrações.</p>
</div>
<?php else: ?>
<div class="card mb-2" style="background:#ecfdf5;border-color:#10b981;font-size:0.9rem">
    <p style="margin:0"><strong>Gateway ativo:</strong> cobranças emitidas via Asaas. Pagamentos confirmados automaticamente pelo webhook.</p>
</div>
<?php endif; ?>
<div class="page-toolbar"><a href="/cobrancas/criar" class="btn-primary"><i class="ph ph-plus"></i> Nova cobrança</a></div>
<div class="card data-card">
<?php if (empty($resultado['items'])): ?>
    <?php
    $icone = 'invoice';
    $titulo = 'Nenhuma cobrança';
    $texto = 'Crie cobranças Pix ou boleto para seus clientes.';
    $acaoUrl = '/cobrancas/criar';
    $acaoLabel = 'Nova cobrança';
    require __DIR__ . '/../partials/empty-state.php';
    ?>
<?php else: ?>
<table><thead><tr><th>Cliente</th><th>Valor</th><th>Vencimento</th><th>Tipo</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($resultado['items'] as $c): ?>
<tr><td><?= htmlspecialchars($c['cliente_nome']) ?></td><td><?= Money::format((float)$c['valor']) ?></td>
<td><?= date('d/m/Y', strtotime($c['vencimento'])) ?></td><td><?= strtoupper($c['tipo']) ?></td>
<td><span class="badge badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
<td><a href="/cobrancas/<?= $c['id'] ?>" class="btn-ghost btn-sm">Ver</a></td></tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div>
