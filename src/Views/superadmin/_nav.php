<?php
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$tabs = [
    ['/superadmin', 'gauge', 'Painel'],
    ['/superadmin/usuarios', 'users', 'Usuários'],
    ['/superadmin/empresas', 'buildings', 'Empresas'],
    ['/superadmin/logins', 'sign-in', 'Logins'],
];
$tabAtivo = fn (string $path): bool => $path === '/superadmin'
    ? $current === '/superadmin'
    : str_starts_with($current, $path);
?>
<nav class="superadmin-nav card" style="margin-bottom:20px;padding:8px 12px;display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach ($tabs as [$path, $icon, $label]): ?>
    <a href="<?= $path ?>" class="btn btn-sm <?= $tabAtivo($path) ? 'btn-primary' : 'btn-ghost' ?>">
        <i class="ph ph-<?= $icon ?>"></i> <?= $label ?>
    </a>
    <?php endforeach; ?>
</nav>
