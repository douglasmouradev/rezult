<?php
/** @var string $title */
$appUrl = rtrim((string) \App\Core\App::config('url'), '/');
$pageTitle = htmlspecialchars($title ?? 'Gestão financeira empresarial');
$description = 'Rezult — controle financeiro empresarial com clareza, segurança e conformidade LGPD.';
$ogImage = $appUrl . '/assets/img/logo-rezult.png?v=7';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <?php require __DIR__ . '/../partials/head-favicon.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($appUrl . '/') ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Rezult">
    <meta property="og:title" content="Rezult — <?= $pageTitle ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($appUrl . '/') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Rezult — <?= $pageTitle ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <title>Rezult — <?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Syne:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="/assets/css/landing.css?v=3">
</head>
<body class="landing-body">
<?= $content ?>
<script src="/assets/js/landing.js?v=2" defer></script>
</body>
</html>
