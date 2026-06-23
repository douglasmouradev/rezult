<div class="page-header">
    <h1>Equipe</h1>
    <p class="text-muted">Membros e convites pendentes</p>
</div>
<div class="grid-2">
    <div class="card">
        <h3>Membros</h3>
        <table class="data-table">
            <thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($membros as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['nome']) ?></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td>
                    <?php if ($m['papel'] === 'dono' || (int)$m['id'] === (int)($_SESSION['usuario_id'] ?? 0)): ?>
                    <?= htmlspecialchars(ucfirst($m['papel'])) ?>
                    <?php else: ?>
                    <form method="post" action="/equipe/<?= (int)$m['id'] ?>/papel" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <select name="papel" class="input btn-sm" onchange="this.form.submit()">
                            <option value="admin" <?= $m['papel']==='admin'?'selected':'' ?>>Admin</option>
                            <option value="operador" <?= $m['papel']==='operador'?'selected':'' ?>>Operador</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($m['papel'] !== 'dono' && (int)$m['id'] !== (int)($_SESSION['usuario_id'] ?? 0)): ?>
                    <form method="post" action="/equipe/<?= (int)$m['id'] ?>/remover" data-confirm="Remover este membro?">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <button type="submit" class="btn-ghost btn-sm">Remover</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Convites pendentes</h3>
        <?php if (empty($convites)): ?>
        <p class="text-muted">Nenhum convite pendente.</p>
        <?php else: ?>
        <ul class="list-plain">
            <?php foreach ($convites as $c): ?>
            <li style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span><?= htmlspecialchars($c['email']) ?> · <?= htmlspecialchars($c['papel']) ?> · expira <?= date('d/m/Y', strtotime($c['expira_em'])) ?></span>
                <form method="post" action="/equipe/convites/<?= (int)$c['id'] ?>/cancelar">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <button type="submit" class="btn-ghost btn-sm">Cancelar</button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <p class="mt-2"><a href="/empresas" class="btn btn-secondary btn-sm">Convidar em Empresas</a></p>
    </div>
</div>
