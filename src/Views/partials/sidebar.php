<?php
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$navMain = [
    ['/dashboard', 'chart-line', 'Dashboard'],
    ['/lancamentos', 'receipt', 'Lançamentos'],
    ['/contas-a-pagar', 'list-checks', 'Contas a pagar'],
    ['/contas-a-receber', 'trend-up', 'Contas a receber'],
    ['/cobrancas', 'invoice', 'Cobranças'],
    ['/conciliacoes', 'bank', 'Conciliação'],
    ['/contas', 'wallet', 'Contas'],
];
$navAvancado = [
    ['/notas-fiscais', 'currency-circle-dollar', 'NFS-e'],
    ['/automacoes', 'lightning', 'Automações'],
    ['/assistente', 'brain', 'Assistente IA'],
];
$navConfig = [
    ['/categorias', 'tag', 'Categorias'],
    ['/metas', 'target', 'Metas'],
    ['/orcamentos', 'chart-bar', 'Orçamento'],
    ['/centros-custo', 'folders', 'Centros de custo'],
    ['/empresas', 'buildings', 'Empresas'],
];
$navRelatorios = [
    ['/relatorios/dre', 'chart-pie', 'DRE'],
    ['/relatorios/fluxo', 'chart-line-up', 'Fluxo de caixa'],
    ['/relatorios/categoria', 'tag', 'Por categoria'],
    ['/relatorios/centro-custo', 'folders', 'Centro de custo'],
];
$isActive = fn (string $path) => str_starts_with($current, $path)
    || ($path === '/relatorios/dre' && str_starts_with($current, '/relatorios'));
?>
<div class="sidebar-overlay" aria-hidden="true"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <?php
        $asLink = true;
        $href = '/dashboard';
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
        <?php foreach ($navMain as [$path, $icon, $label]): ?>
        <a href="<?= $path ?>" class="nav-item <?= $isActive($path) ? 'active' : '' ?>">
            <i class="ph ph-<?= $icon ?>"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <p class="sidebar-section">Avançado</p>
    <nav class="sidebar-nav">
        <?php foreach ($navAvancado as [$path, $icon, $label]): ?>
        <a href="<?= $path ?>" class="nav-item <?= $isActive($path) ? 'active' : '' ?>">
            <i class="ph ph-<?= $icon ?>"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <p class="sidebar-section">Conta</p>
    <nav class="sidebar-nav">
        <a href="/privacidade/meus-dados" class="nav-item <?= str_starts_with($current, '/privacidade') ? 'active' : '' ?>">
            <i class="ph ph-shield-check"></i>
            <span>Privacidade (LGPD)</span>
        </a>
    </nav>

    <?php if (!empty($podeGerenciar)): ?>
    <p class="sidebar-section">Administração</p>
    <nav class="sidebar-nav">
        <a href="/equipe" class="nav-item <?= str_starts_with($current, '/equipe') ? 'active' : '' ?>">
            <i class="ph ph-users"></i><span>Equipe</span>
        </a>
        <a href="/auditoria" class="nav-item <?= str_starts_with($current, '/auditoria') ? 'active' : '' ?>">
            <i class="ph ph-list-checks"></i><span>Auditoria</span>
        </a>
        <a href="/api/tokens" class="nav-item <?= str_starts_with($current, '/api') ? 'active' : '' ?>">
            <i class="ph ph-code"></i><span>API</span>
        </a>
    </nav>
    <?php endif; ?>

    <p class="sidebar-section">Configuração</p>
    <nav class="sidebar-nav">
        <?php foreach ($navConfig as [$path, $icon, $label]): ?>
        <a href="<?= $path ?>" class="nav-item <?= $isActive($path) ? 'active' : '' ?>">
            <i class="ph ph-<?= $icon ?>"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <p class="sidebar-section">Relatórios</p>
    <nav class="sidebar-nav">
        <?php foreach ($navRelatorios as [$path, $icon, $label]): ?>
        <a href="<?= $path ?>" class="nav-item <?= $isActive($path) ? 'active' : '' ?>">
            <i class="ph ph-<?= $icon ?>"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    </div>

    <div class="sidebar-footer">
        <small>Rezult · Gestão financeira</small>
    </div>
</aside>
