<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <?php require __DIR__ . '/../partials/head-favicon.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf ?? '') ?>">
    <title><?= $title ?? 'Rezult' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="/assets/css/app.css?v=corp6">
</head>
<body class="guest-body">
    <div class="guest-wrap">
        <div class="guest-brand">
            <?php
            $asLink = false;
            $class = 'brand-logo--guest';
            $imgClass = 'brand-logo__img--guest';
            $showText = false;
            $imgHeight = 52;
            require dirname(__DIR__) . '/partials/brand-logo.php';
            ?>
            <p class="logo-tagline">Gestão financeira corporativa — clara, segura e profissional.</p>
            <ul class="guest-features">
                <li><i class="ph ph-chart-line-up"></i> Dashboard com visão de caixa em tempo real</li>
                <li><i class="ph ph-buildings"></i> Várias empresas no mesmo login</li>
                <li><i class="ph ph-shield-check"></i> Dados isolados e seguros por empresa</li>
            </ul>
        </div>
        <div class="guest-card card">
            <div class="guest-mobile-brand">
                <?php
                $asLink = true;
                $href = '/';
                $class = 'brand-logo--guest-mobile';
                $imgClass = 'brand-logo__img--guest-mobile';
                $showText = false;
                $imgHeight = 40;
                require dirname(__DIR__) . '/partials/brand-logo.php';
                ?>
            </div>
            <?= $content ?>
        </div>
    </div>
    <div id="toast-container" class="toast-container"></div>
    <?php require __DIR__ . '/../partials/cookie-banner.php'; ?>
    <script src="/assets/js/app.js?v=3"></script>
</body>
</html>
