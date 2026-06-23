<?php
$svc = new \App\Services\SuperAdminService();
$planSvc = new \App\Services\PlanService();
$meuId = (int)($_SESSION['usuario_id'] ?? 0);
$status = $svc->statusUsuario($usuario);
$excluido = $status === 'excluido';
?>
<div class="page-header">
    <h1><?= htmlspecialchars((string) ($usuario['nome'] ?? '')) ?></h1>
    <p class="text-muted"><?= htmlspecialchars((string) ($usuario['email'] ?? '')) ?> · ID #<?= (int)($usuario['id'] ?? 0) ?></p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="grid-2" style="margin-bottom:20px">
    <div class="card">
        <h3>Dados da conta</h3>
        <?php if ($excluido): ?>
        <p class="text-muted">Conta excluída em <?= date('d/m/Y H:i', strtotime($usuario['excluido_em'])) ?>.</p>
        <?php else: ?>
        <form method="post" action="/superadmin/usuarios/<?= (int)$usuario['id'] ?>">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <label>Nome</label>
            <input class="input" name="nome" value="<?= htmlspecialchars((string) ($usuario['nome'] ?? '')) ?>" required>
            <label class="mt-2">E-mail</label>
            <input class="input" type="email" name="email" value="<?= htmlspecialchars((string) ($usuario['email'] ?? '')) ?>" required>
            <label class="mt-2 checkbox-label">
                <input type="checkbox" name="email_verificado" value="1" <?= (int)($usuario['email_verificado'] ?? 0) ? 'checked' : '' ?>>
                E-mail verificado
            </label>
            <label class="mt-2 checkbox-label">
                <input type="checkbox" name="is_superadmin" value="1" <?= (int)($usuario['is_superadmin'] ?? 0) ? 'checked' : '' ?> <?= (int)$usuario['id'] === $meuId ? 'disabled' : '' ?>>
                Superadmin
            </label>
            <?php if ((int)$usuario['id'] === $meuId): ?>
            <input type="hidden" name="is_superadmin" value="1">
            <?php endif; ?>
            <label class="mt-2 checkbox-label">
                <input type="checkbox" name="bloqueado" value="1" <?= (int)($usuario['bloqueado'] ?? 0) ? 'checked' : '' ?> <?= (int)$usuario['id'] === $meuId ? 'disabled' : '' ?>>
                Conta bloqueada
            </label>
            <button type="submit" class="btn btn-primary mt-2">Salvar alterações</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Informações</h3>
        <ul class="alert-list">
            <li><strong>Cadastro:</strong> <?= !empty($usuario['criado_em']) ? date('d/m/Y H:i', strtotime((string) $usuario['criado_em'])) : '—' ?></li>
            <li><strong>Último login:</strong> <?= !empty($usuario['ultimo_login_em']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_login_em'])) : 'Nunca' ?></li>
            <li><strong>Status:</strong>
                <?php if ($status === 'ativo'): ?><span class="badge badge-pago">Ativo</span>
                <?php elseif ($status === 'bloqueado'): ?><span class="badge badge-pendente">Bloqueado</span>
                <?php elseif ($status === 'excluido'): ?><span class="badge">Excluído</span>
                <?php else: ?><span class="badge badge-pendente">Inativo</span><?php endif; ?>
            </li>
            <li><strong>Sessão lembrar:</strong> <?= (int)($usuario['sessoes_lembrar'] ?? 0) > 0 ? 'Ativa' : 'Não' ?></li>
            <li><strong>Empresas:</strong> <?= (int)($usuario['empresas_qtd'] ?? 0) ?></li>
        </ul>

        <?php if (!$excluido): ?>
        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
            <?php if ((int)$usuario['id'] !== $meuId): ?>
            <form method="post" action="/superadmin/usuarios/<?= (int)$usuario['id'] ?>/bloquear" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn-ghost btn-sm"><?= (int)($usuario['bloqueado'] ?? 0) ? 'Desbloquear' : 'Bloquear' ?></button>
            </form>
            <?php endif; ?>
            <form method="post" action="/superadmin/usuarios/<?= (int)$usuario['id'] ?>/sessoes" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn-ghost btn-sm">Encerrar sessões</button>
            </form>
            <?php if ((int)$usuario['id'] !== $meuId): ?>
            <form method="post" action="/superadmin/usuarios/<?= (int)$usuario['id'] ?>/excluir" class="inline-form" onsubmit="return confirm('Excluir e anonimizar este usuário? Esta ação não pode ser desfeita.')">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn-ghost btn-sm" style="color:var(--red)">Excluir conta</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$excluido): ?>
<div class="card" style="margin-bottom:20px">
    <h3>Redefinir senha</h3>
    <form method="post" action="/superadmin/usuarios/<?= (int)$usuario['id'] ?>/senha" class="grid-2" style="align-items:end">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div>
            <label>Nova senha</label>
            <input class="input" type="password" name="senha" minlength="8" required>
        </div>
        <div>
            <label>Confirmar senha</label>
            <input class="input" type="password" name="senha_confirmacao" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-secondary">Redefinir senha</button>
    </form>
</div>
<?php endif; ?>

<div class="grid-2">
    <div class="card table-card">
        <h3>Empresas vinculadas</h3>
        <?php if (empty($empresas)): ?>
        <p class="text-muted">Nenhuma empresa vinculada.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Empresa</th><th>Papel</th><th>Plano</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($empresas as $e): ?>
                <tr>
                    <td><a href="/superadmin/empresas"><?= htmlspecialchars($e['nome']) ?></a></td>
                    <td><?= ucfirst($e['papel']) ?></td>
                    <td><?= $planSvc->planoLabel($e['plano'] ?? 'starter') ?></td>
                    <td>
                        <?php $st = $svc->statusPlano($e); ?>
                        <span class="badge <?= $st === 'ativa' ? 'badge-pago' : 'badge-pendente' ?>"><?= $st === 'ativa' ? 'Ativa' : 'Inativa' ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="card table-card">
        <h3>Histórico de logins</h3>
        <?php if (empty($logins)): ?>
        <p class="text-muted">Nenhum registro de login.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Data</th><th>IP</th><th>Resultado</th></tr></thead>
                <tbody>
                <?php foreach ($logins as $l): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($l['criado_em'])) ?></td>
                    <td><code><?= htmlspecialchars($l['ip']) ?></code></td>
                    <td><span class="badge <?= (int)$l['sucesso'] ? 'badge-pago' : 'badge-pendente' ?>"><?= (int)$l['sucesso'] ? 'Sucesso' : 'Falha' ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<p style="margin-top:16px"><a href="/superadmin/usuarios" class="btn-ghost btn-sm">← Voltar à lista</a></p>
