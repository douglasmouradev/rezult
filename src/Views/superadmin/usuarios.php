<?php $svc = new \App\Services\SuperAdminService(); $meuId = (int)($_SESSION['usuario_id'] ?? 0); ?>
<div class="page-header">
    <h1>Usuários</h1>
    <p class="text-muted">Todos os usuários da plataforma</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="card table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Verificado</th>
                    <th>Empresas</th>
                    <th>Último login</th>
                    <th>Status</th>
                    <th>Sessão lembrar</th>
                    <th>Superadmin</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['nome']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= (int)$u['email_verificado'] ? 'Sim' : 'Não' ?></td>
                <td><?= (int) $u['empresas_qtd'] ?></td>
                <td><?= $u['ultimo_login_em'] ? date('d/m/Y H:i', strtotime($u['ultimo_login_em'])) : 'Nunca' ?></td>
                <td>
                    <?php if ($svc->estaAtivo($u['ultimo_login_em'])): ?>
                    <span class="badge badge-pago">Ativo</span>
                    <?php else: ?>
                    <span class="badge badge-pendente">Inativo</span>
                    <?php endif; ?>
                </td>
                <td><?= (int)$u['sessoes_lembrar'] > 0 ? 'Sim' : '—' ?></td>
                <td>
                    <?php if ((int)$u['is_superadmin']): ?>
                    <?php if ((int)$u['id'] !== $meuId): ?>
                    <form method="post" action="/superadmin/revogar" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn-ghost btn-sm">Revogar</button>
                    </form>
                    <?php else: ?>
                    <span class="badge">Você</span>
                    <?php endif; ?>
                    <?php else: ?>
                    <form method="post" action="/superadmin/promover" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn-ghost btn-sm">Promover</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
