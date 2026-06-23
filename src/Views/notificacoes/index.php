<div class="page-header">
    <h1>Notificações</h1>
    <div style="display:flex;gap:8px">
        <a href="/notificacoes?filtro=todas" class="btn-ghost btn-sm <?= !empty($filtroTodas) ? 'active' : '' ?>">Histórico</a>
        <a href="/notificacoes" class="btn-ghost btn-sm <?= empty($filtroTodas) ? 'active' : '' ?>">Não lidas</a>
        <form method="post" action="/notificacoes/lidas"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <button class="btn btn-secondary btn-sm">Marcar todas como lidas</button></form>
    </div>
</div>
<div class="card">
<?php if (empty($notificacoes)): ?>
<p class="text-muted">Nenhuma notificação<?= empty($filtroTodas) ? ' nova' : '' ?>.</p>
<?php else: foreach ($notificacoes as $n): ?>
<div class="notif-item <?= !empty($n['lida']) ? 'notif-lida' : '' ?>">
    <strong><?= htmlspecialchars($n['titulo']) ?></strong>
    <p><?= htmlspecialchars($n['mensagem'] ?? '') ?></p>
    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></small>
    <?php if (empty($n['lida'])): ?>
    <form method="post" action="/notificacoes/<?= (int)$n['id'] ?>/lida"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <button class="btn-ghost btn-sm">Marcar lida</button></form>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>
