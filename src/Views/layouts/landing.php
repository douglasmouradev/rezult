<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <?php require __DIR__ . '/../partials/head-favicon.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rezult — controle financeiro empresarial com clareza, segurança e conformidade LGPD.">
    <title>Rezult — <?= htmlspecialchars($title ?? 'Gestão financeira empresarial') ?></title>
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
