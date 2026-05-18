<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="page-actions"><a href="/empresas/criar" class="btn-primary">Nova empresa</a></div>
<div class="grid-2">
<?php foreach ($empresas as $e):
    $podeAdmin = in_array($e['papel'], ['dono', 'admin'], true);
?>
<div class="card">
    <h3><?= htmlspecialchars($e['nome']) ?></h3>
    <p class="text-muted" style="font-size:0.85rem"><?= htmlspecialchars($e['cnpj'] ?? '') ?> · <?= ucfirst($e['papel']) ?></p>
    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
        <form method="post" action="/empresas/<?= $e['id'] ?>/trocar">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button class="btn-primary btn-sm">Usar esta</button>
        </form>
        <?php if ($podeAdmin): ?>
        <a href="/empresas/<?= $e['id'] ?>/editar" class="btn-ghost btn-sm">Editar</a>
        <?php endif; ?>
    </div>
    <?php if ($podeAdmin): ?>
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
