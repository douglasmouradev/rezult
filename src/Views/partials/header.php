<?php
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$planSvcHeader = new \App\Services\PlanService();
$iniciais = '';
$initial = static function (string $s): string {
    if ($s === '') {
        return '';
    }
    return function_exists('mb_substr') ? mb_substr($s, 0, 1) : substr($s, 0, 1);
};
if (!empty($usuario['nome'])) {
    $partes = preg_split('/\s+/', trim($usuario['nome']));
    $iniciais = strtoupper($initial($partes[0]) . (isset($partes[1]) ? $initial($partes[1]) : ''));
}
$meses = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$mesAtual = $meses[(int) date('n')] . ' ' . date('Y');
?>
<header class="top-header">
    <div class="header-left">
        <button type="button" class="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
            <?php require __DIR__ . '/icon-menu.php'; ?>
        </button>
        <div>
            <h1 class="page-title"><?= htmlspecialchars($title ?? '') ?></h1>
            <?php if (!empty($empresa['nome'])): ?>
            <p class="page-subtitle"><?= htmlspecialchars($empresa['nome']) ?> · <?= ucfirst($mesAtual) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="header-actions">
        <button type="button" class="btn-ghost btn-sm btn-with-icon theme-toggle" aria-label="Alternar tema claro/escuro" title="Alternar tema">
            <i class="ph ph-moon" aria-hidden="true"></i>
            <span class="btn-label">Tema</span>
        </button>
        <a href="/notificacoes" class="btn-ghost btn-sm btn-with-icon notif-bell" title="Notificações" aria-label="Notificações<?= !empty($notifCount) ? " ({$notifCount} novas)" : '' ?>">
            <i class="ph ph-bell" aria-hidden="true"></i>
            <span class="btn-label">Alertas</span>
            <?php if (!empty($notifCount)): ?><span class="notif-badge"><?= (int)$notifCount ?></span><?php endif; ?>
        </a>
        <a href="/perfil" class="btn-ghost btn-sm btn-with-icon" title="Meu perfil">
            <i class="ph ph-user" aria-hidden="true"></i>
            <span class="btn-label">Perfil</span>
        </a>
        <?php if (!str_contains($_SERVER['REQUEST_URI'] ?? '', '/lancamentos') && !empty($empresa)): ?>
        <a href="/lancamentos/criar" class="btn-primary btn-sm btn-header-lancamento">
            <i class="ph ph-plus" aria-hidden="true"></i><span class="btn-header-lancamento__label"> Lançamento</span>
        </a>
        <?php endif; ?>
        <?php if (!empty($empresasMenu)): ?>
        <form method="post" action="/empresas/<?= (int)($empresa['id'] ?? 0) ?>/trocar" class="empresa-selector" id="empresa-form">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <select name="empresa_switch" aria-label="Empresa ativa" onchange="if(!this.options[this.selectedIndex].disabled){this.form.action='/empresas/'+this.value+'/trocar';this.form.submit()}">
                <?php foreach ($empresasMenu as $e):
                    $bloqueio = $planSvcHeader->motivoBloqueio($e);
                    $operacional = $bloqueio === null;
                ?>
                <option value="<?= $e['id'] ?>" <?= !$operacional ? 'disabled' : '' ?> <?= ($empresa['id'] ?? 0) == $e['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['nome']) ?><?= !$operacional ? ' (indisponível)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
        <div class="user-chip">
            <span><?= htmlspecialchars($usuario['nome'] ?? '') ?></span>
            <span class="user-avatar" title="<?= htmlspecialchars($usuario['nome'] ?? '') ?>"><?= $iniciais ?></span>
            <form method="post" action="/logout">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn-ghost btn-sm btn-with-icon" aria-label="Sair da conta" title="Sair">
                    <i class="ph ph-sign-out" aria-hidden="true"></i>
                    <span class="btn-label">Sair</span>
                </button>
            </form>
        </div>
    </div>
</header>
