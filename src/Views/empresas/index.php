<?php require __DIR__ . '/../partials/flash.php'; ?>
<?php $planSvc = new \App\Services\PlanService(); ?>
<div class="page-actions"><a href="/empresas/criar" class="btn-primary">Nova empresa</a></div>
<div class="grid-2">
<?php foreach ($empresas as $e):
    $podeAdmin = in_array($e['papel'], ['dono', 'admin'], true);
    $bloqueio = $planSvc->motivoBloqueio($e);
    $operacional = $bloqueio === null;
?>
<div class="card <?= !$operacional ? 'card--muted' : '' ?>">
    <h3><?= htmlspecialchars($e['nome']) ?></h3>
    <p class="text-muted" style="font-size:0.85rem"><?= htmlspecialchars($e['cnpj'] ?? '') ?> · <?= ucfirst($e['papel']) ?></p>
    <?php if (!$operacional): ?>
    <p class="text-muted" style="font-size:0.85rem;color:var(--red);margin-top:8px"><?= htmlspecialchars($bloqueio) ?></p>
    <?php else: ?>
    <p class="text-muted" style="font-size:0.85rem;margin-top:8px">
        Plano: <strong><?= htmlspecialchars($planSvc->planoLabel($e['plano'] ?? 'starter')) ?></strong>
        <?php if (!empty($e['plano_expira_em'])): ?>
        · expira <?= date('d/m/Y', strtotime($e['plano_expira_em'])) ?>
        <?php endif; ?>
    </p>
    <?php endif; ?>
    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
        <?php if ($operacional): ?>
        <form method="post" action="/empresas/<?= $e['id'] ?>/trocar">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button class="btn-primary btn-sm">Usar esta</button>
        </form>
        <?php endif; ?>
        <?php if ($podeAdmin && $operacional): ?>
        <a href="/empresas/<?= $e['id'] ?>/editar" class="btn-ghost btn-sm">Editar</a>
        <?php endif; ?>
    </div>
    <?php if ($podeAdmin && $operacional): ?>
    <form method="post" action="/empresas/<?= $e['id'] ?>/convidar" style="margin-top:16px;display:flex;gap:8px">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <input class="input" name="email" placeholder="E-mail do colaborador" style="flex:1">
        <select name="papel" class="input" style="width:auto"><option value="operador">Operador</option><option value="admin">Admin</option></select>
        <button class="btn-ghost btn-sm">Convidar</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
