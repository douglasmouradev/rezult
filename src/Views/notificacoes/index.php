<div class="page-header">
    <h1>Notificações</h1>
    <form method="post" action="/notificacoes/lidas"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <button class="btn btn-secondary btn-sm">Marcar todas como lidas</button></form>
</div>
<div class="card">
<?php if (empty($notificacoes)): ?>
<p class="text-muted">Nenhuma notificação nova.</p>
<?php else: foreach ($notificacoes as $n): ?>
<div class="notif-item">
    <strong><?= htmlspecialchars($n['titulo']) ?></strong>
    <p><?= htmlspecialchars($n['mensagem'] ?? '') ?></p>
    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></small>
    <form method="post" action="/notificacoes/<?= (int)$n['id'] ?>/lida"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <button class="btn-ghost btn-sm">Marcar lida</button></form>
</div>
<?php endforeach; endif; ?>
</div>
