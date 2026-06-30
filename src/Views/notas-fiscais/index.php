<?php use App\Helpers\Money; require __DIR__ . '/../partials/flash.php'; ?>
<?php if (!empty($isProduction)): ?>
<div class="card mb-2" style="background:#fff8e6;border-color:#f59e0b;font-size:0.9rem">
    <p style="margin:0">Em produção, a emissão NFS-e exige integração com a prefeitura. O modo demonstração está desativado por padrão.</p>
</div>
<?php else: ?>
<div class="card mb-2" style="background:#eff6ff;border-color:#2563eb;font-size:0.9rem">
    <p style="margin:0">NFS-e em <strong>modo demonstração</strong> — números gerados localmente, sem validade fiscal.</p>
</div>
<?php endif; ?>
<div class="page-toolbar"><a href="/notas-fiscais/criar" class="btn-primary"><i class="ph ph-plus"></i> Nova NFS-e</a></div>
<div class="card data-card">
<?php if (empty($resultado['items'])): ?>
    <?php
    $icone = 'receipt';
    $titulo = 'Nenhuma nota fiscal';
    $texto = 'Emita NFS-e de serviço para seus clientes.';
    $acaoUrl = '/notas-fiscais/criar';
    $acaoLabel = 'Nova NFS-e';
    require __DIR__ . '/../partials/empty-state.php';
    ?>
<?php else: ?>
<table><thead><tr><th>Tomador</th><th>Valor</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($resultado['items'] as $n): ?>
<tr><td><?= htmlspecialchars($n['tomador_nome']) ?></td><td><?= Money::format((float)$n['valor']) ?></td>
<td><?= ucfirst($n['status']) ?></td><td><a href="/notas-fiscais/<?= $n['id'] ?>">Ver</a></td></tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div>
