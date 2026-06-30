<?php
use App\Helpers\NavHelper;

$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$navMain = NavHelper::navMain();
$navAvancado = NavHelper::navAvancado();
$navConfig = NavHelper::navConfig();
$navRelatorios = [
    ['/relatorios/dre', 'chart-pie', 'DRE'],
    ['/relatorios/fluxo', 'chart-line-up', 'Fluxo de caixa'],
    ['/relatorios/categoria', 'tag', 'Por categoria'],
    ['/relatorios/centro-custo', 'folders', 'Centro de custo'],
];
$isActive = fn (string $path) => str_starts_with($current, $path)
    || ($path === '/relatorios/dre' && str_starts_with($current, '/relatorios'));

$renderNavItems = static function (array $items) use ($current, $navUrl, $isActive, $empresaId): void {
    foreach ($items as $entry) {
        [$path, $icon, $label] = $entry;
        $feature = $entry[3] ?? null;
        $badge = $entry[4] ?? NavHelper::badgePlano($feature);
        $locked = $feature !== null && !NavHelper::temFeature((int) $empresaId, $feature);
        $href = $locked ? '/plano' : $navUrl($path);
        $classes = 'nav-item' . ($isActive($path) ? ' active' : '') . ($locked ? ' nav-item--locked' : '');
        ?>
        <a href="<?= htmlspecialchars($href) ?>" class="<?= $classes ?>"<?= $locked ? ' title="Disponível no plano ' . htmlspecialchars((string) $badge) . '"' : '' ?>>
            <i class="ph ph-<?= $icon ?>"></i>
            <span><?= htmlspecialchars($label) ?></span>
            <?php if ($locked && $badge): ?>
            <span class="nav-badge"><?= htmlspecialchars($badge) ?></span>
            <?php endif; ?>
        </a>
        <?php
    }
};
?>
<div class="sidebar-overlay" aria-hidden="true"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <button type="button" class="sidebar-close" aria-label="Fechar menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/>
            </svg>
        </button>
        <?php
        $asLink = true;
        $href = $navUrl('/dashboard');
        $class = 'brand-logo--sidebar';
        $imgClass = 'brand-logo__img--sidebar';
        $showText = false;
        $imgHeight = 34;
        require __DIR__ . '/brand-logo.php';
        ?>
    </div>

    <div class="sidebar-menu">
    <p class="sidebar-section">Financeiro</p>
    <nav class="sidebar-nav">
        <?php $renderNavItems($navMain); ?>
    </nav>

    <p class="sidebar-section">Avançado</p>
    <nav class="sidebar-nav">
        <?php $renderNavItems($navAvancado); ?>
    </nav>

    <p class="sidebar-section">Conta</p>
    <nav class="sidebar-nav">
        <a href="/privacidade/meus-dados" class="nav-item <?= str_starts_with($current, '/privacidade') ? 'active' : '' ?>">
            <i class="ph ph-shield-check"></i>
            <span>Privacidade (LGPD)</span>
        </a>
    </nav>

    <?php if (!empty($isSuperadmin)): ?>
    <p class="sidebar-section">Plataforma</p>
    <nav class="sidebar-nav">
        <a href="/superadmin" class="nav-item <?= str_starts_with($current, '/superadmin') ? 'active' : '' ?>">
            <i class="ph ph-shield-star"></i><span>Superadmin</span>
        </a>
    </nav>
    <?php endif; ?>

    <?php if (!empty($podeGerenciar)): ?>
    <p class="sidebar-section">Administração</p>
    <nav class="sidebar-nav">
        <?php
        $adminItems = [
            ['/equipe', 'users', 'Equipe', 'equipe', null],
            ['/auditoria', 'list-checks', 'Auditoria', null, null],
            ['/api/tokens', 'code', 'API', 'api', 'Pro'],
            ['/webhooks', 'webhooks-logo', 'Webhooks', 'webhooks', 'Pro'],
        ];
        $renderNavItems($adminItems);
        ?>
    </nav>
    <?php endif; ?>

    <p class="sidebar-section">Configuração</p>
    <nav class="sidebar-nav">
        <?php $renderNavItems($navConfig); ?>
    </nav>

    <p class="sidebar-section">Relatórios</p>
    <nav class="sidebar-nav">
        <?php foreach ($navRelatorios as [$path, $icon, $label]): ?>
        <a href="<?= $navUrl($path) ?>" class="nav-item <?= $isActive($path) ? 'active' : '' ?>">
            <i class="ph ph-<?= $icon ?>"></i>
            <span><?= htmlspecialchars($label) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    </div>

    <div class="sidebar-footer">
        <small>Rezult · Gestão financeira</small>
    </div>
</aside>
