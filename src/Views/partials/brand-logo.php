<?php
/** @var bool $asLink */
/** @var string $href */
/** @var string $class */
/** @var string $imgClass */
/** @var bool $showText */
/** @var int $imgHeight */
$asLink = $asLink ?? true;
$href = $href ?? '/';
$class = $class ?? '';
$imgClass = $imgClass ?? '';
$showText = $showText ?? false;
$imgHeight = $imgHeight ?? 36;
$logoAspect = 130 / 132;
$imgWidth = max(1, (int) round($imgHeight * $logoAspect));
$logoSrc = '/assets/img/logo-rezult.png?v=7';
$tagOpen = $asLink ? '<a href="' . htmlspecialchars($href) . '"' : '<div';
$tagClose = $asLink ? 'a' : 'div';
?>
<?= $tagOpen ?> class="brand-logo <?= htmlspecialchars($class) ?>">
    <img
        src="<?= htmlspecialchars($logoSrc) ?>"
        alt="Rezult"
        class="brand-logo__img <?= htmlspecialchars($imgClass) ?>"
        width="<?= $imgWidth ?>"
        height="<?= $imgHeight ?>"
        loading="lazy"
        decoding="async"
    >
    <?php if ($showText): ?><span class="logo-text">Rezult</span><?php endif; ?>
</<?= $tagClose ?>>
