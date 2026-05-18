<?php
/** @var string $variant saldo|receita|despesa|resultado */
/** @var string $label */
/** @var float $value */
/** @var string $icon Phosphor icon name without ph- prefix */
/** @var string|null $hint */
use App\Helpers\Money;
$p = Money::parts($value);
?>
<div class="stat-card stat-<?= $variant ?> card-interactive">
    <div class="stat-head">
        <span class="stat-label"><?= htmlspecialchars($label) ?></span>
        <span class="stat-icon"><i class="ph ph-<?= $icon ?>"></i></span>
    </div>
    <div class="stat-body">
        <div class="stat-value">
            <span class="currency"><?= $p['symbol'] ?></span>
            <span class="amount"><?= $p['amount'] ?></span>
        </div>
        <?php if (!empty($hint)): ?>
        <span class="stat-hint <?= ($variant === 'resultado' && $value < 0) ? 'negative' : (($variant === 'resultado') ? 'positive' : '') ?>">
            <?= htmlspecialchars($hint) ?>
        </span>
        <?php endif; ?>
    </div>
</div>
