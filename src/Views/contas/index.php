<?php use App\Helpers\Money; ?>
<div class="page-toolbar">
    <p class="page-subtitle" style="margin:0"><?= count($contas) ?> conta(s) ativas</p>
    <div class="page-actions">
        <a href="/contas/transferir" class="btn-ghost btn-sm"><i class="ph ph-arrows-left-right"></i> Transferir</a>
        <a href="/contas/criar" class="btn-primary"><i class="ph ph-plus"></i> Nova conta</a>
    </div>
</div>
<div class="grid-2">
<?php foreach ($contas as $c): ?>
<div class="card card-interactive account-card">
    <div class="account-card-top">
        <div>
            <h3 style="font-family:Syne;font-size:1.1rem"><?= htmlspecialchars($c['nome']) ?></h3>
            <span class="account-type"><?= ucfirst($c['tipo']) ?></span>
        </div>
        <span class="account-dot" style="background:<?= htmlspecialchars($c['cor']) ?>"></span>
    </div>
    <p class="account-balance"><?= Money::format($c['saldo_atual']) ?></p>
    <div class="account-actions">
        <a href="/contas/<?= $c['id'] ?>/extrato" class="btn-ghost btn-sm"><i class="ph ph-list-dashes"></i> Extrato</a>
        <a href="/contas/<?= $c['id'] ?>/editar" class="btn-ghost btn-sm">Editar</a>
    </div>
</div>
<?php endforeach; ?>
</div>
