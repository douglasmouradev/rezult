<?php $svc = new \App\Services\SuperAdminService(); $meuId = (int)($_SESSION['usuario_id'] ?? 0); ?>
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
    <div>
        <h1>Usuários</h1>
        <p class="text-muted">Gestão completa de contas da plataforma</p>
    </div>
    <a href="/superadmin/usuarios/criar" class="btn-primary btn-sm"><i class="ph ph-user-plus"></i> Novo usuário</a>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="filters card" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <span class="text-muted">Filtrar:</span>
    <a href="/superadmin/usuarios" class="btn btn-sm <?= $filtro === '' ? 'btn-primary' : 'btn-ghost' ?>">Todos</a>
    <a href="/superadmin/usuarios?filtro=ativos" class="btn btn-sm <?= $filtro === 'ativos' ? 'btn-primary' : 'btn-ghost' ?>">Ativos</a>
    <a href="/superadmin/usuarios?filtro=bloqueados" class="btn btn-sm <?= $filtro === 'bloqueados' ? 'btn-primary' : 'btn-ghost' ?>">Bloqueados</a>
    <a href="/superadmin/usuarios?filtro=excluidos" class="btn btn-sm <?= $filtro === 'excluidos' ? 'btn-primary' : 'btn-ghost' ?>">Excluídos</a>
</div>

<div class="card table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Verificado</th>
                    <th>Empresas</th>
                    <th>Último login</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u):
                $status = $svc->statusUsuario($u);
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($u['nome']) ?></strong>
                    <?php if ((int)($u['is_superadmin'] ?? 0)): ?><span class="badge" style="margin-left:4px">SA</span><?php endif; ?>
                    <div class="td-muted"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td><?= (int)($u['email_verificado'] ?? 0) ? 'Sim' : 'Não' ?></td>
                <td><?= (int) $u['empresas_qtd'] ?></td>
                <td><?= !empty($u['ultimo_login_em']) ? date('d/m/Y H:i', strtotime($u['ultimo_login_em'])) : 'Nunca' ?></td>
                <td>
                    <?php if ($status === 'ativo'): ?>
                    <span class="badge badge-pago">Ativo</span>
                    <?php elseif ($status === 'bloqueado'): ?>
                    <span class="badge badge-pendente">Bloqueado</span>
                    <?php elseif ($status === 'excluido'): ?>
                    <span class="badge">Excluído</span>
                    <?php else: ?>
                    <span class="badge badge-pendente">Inativo</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                    <a href="/superadmin/usuarios/<?= (int)$u['id'] ?>" class="btn-ghost btn-sm">Gerenciar</a>
                    <?php if ($status !== 'excluido' && (int)$u['id'] !== $meuId): ?>
                    <form method="post" action="/superadmin/usuarios/<?= (int)$u['id'] ?>/bloquear" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <button type="submit" class="btn-ghost btn-sm"><?= $status === 'bloqueado' ? 'Desbloquear' : 'Bloquear' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
