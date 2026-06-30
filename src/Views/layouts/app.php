<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <?php require __DIR__ . '/../partials/head-favicon.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf ?? '') ?>">
    <title><?= $title ?? 'Rezult' ?> — <?= htmlspecialchars($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js" defer></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="/assets/css/app.css?v=corp9">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="main-area">
        <?php require __DIR__ . '/../partials/header.php'; ?>
        <main class="page-content">
            <?php require __DIR__ . '/../partials/flash.php'; ?>
            <?php if (!empty($navSemEmpresa) && ($navSemEmpresa === '/empresas/criar' || $navSemEmpresa === '/empresas')): ?>
<div class="card mb-2" style="background:var(--surface-2);border-color:var(--primary)">
    <p style="margin:0">
        <?php if ($navSemEmpresa === '/empresas/criar'): ?>
        <strong>Configure sua empresa</strong> para liberar o dashboard, lançamentos e relatórios. Use o menu <em>Empresas</em> ou preencha o formulário abaixo.
        <?php else: ?>
        <strong>Nenhuma loja ativa.</strong> Acesse <a href="/empresas">Empresas</a> para verificar o status do plano ou fale com o suporte.
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"></div>
<?php require __DIR__ . '/../partials/cookie-banner.php'; ?>
<script src="/assets/js/app.js?v=8"></script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
}
</script>
<?php if (!empty($pageScripts)): ?><?= $pageScripts ?><?php endif; ?>
</body>
</html>
