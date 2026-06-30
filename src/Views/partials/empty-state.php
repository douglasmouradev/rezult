<?php
/** @var string $icone ph icon name */
/** @var string $titulo */
/** @var string $texto */
/** @var string|null $acaoUrl */
/** @var string|null $acaoLabel */
?>
<div class="empty-state">
    <i class="ph ph-<?= htmlspecialchars($icone ?? 'folder-open') ?>" aria-hidden="true"></i>
    <h3><?= htmlspecialchars($titulo) ?></h3>
    <p class="text-muted"><?= htmlspecialchars($texto) ?></p>
    <?php if (!empty($acaoUrl)): ?>
    <a href="<?= htmlspecialchars($acaoUrl) ?>" class="btn-primary btn-sm"><?= htmlspecialchars($acaoLabel ?? 'Começar') ?></a>
    <?php endif; ?>
</div>
