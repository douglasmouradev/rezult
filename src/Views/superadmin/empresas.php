<?php
$svc = new \App\Services\SuperAdminService();
$planSvc = new \App\Services\PlanService();
$catalogo = $planSvc->catalogoPlanos();
$fmtDt = static fn (?string $v): string => \App\Helpers\DateTimeBr::toDatetimeLocal($v);
$fmtView = static fn (?string $v): string => \App\Helpers\DateTimeBr::format($v);
$lojas = $lojas ?? [];
?>
<div class="page-header">
    <h1>Lojas</h1>
    <p class="text-muted">Gerencie planos, validação e status das lojas cadastradas</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="filters card" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <span class="text-muted">Filtrar:</span>
    <a href="/superadmin/empresas" class="btn btn-sm <?= $filtro === '' ? 'btn-primary' : 'btn-ghost' ?>">Todas</a>
    <a href="/superadmin/empresas?filtro=ativo" class="btn btn-sm <?= $filtro === 'ativo' ? 'btn-primary' : 'btn-ghost' ?>">Plano ativo</a>
    <a href="/superadmin/empresas?filtro=inativo" class="btn btn-sm <?= $filtro === 'inativo' ? 'btn-primary' : 'btn-ghost' ?>">Inativas / desabilitadas</a>
</div>

<div class="card table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Loja</th>
                    <th>Dono</th>
                    <th>Plano</th>
                    <th>Status</th>
                    <th>Expira / Trial</th>
                    <th>Membros</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lojas as $e):
                $status = $svc->statusPlano($e);
                $eid = (int) $e['id'];
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($e['nome']) ?></strong>
                    <div class="td-muted">#<?= $eid ?> · <?= htmlspecialchars($e['cnpj'] ?? '—') ?></div>
                </td>
                <td>
                    <?= htmlspecialchars($e['dono_nome'] ?? '—') ?>
                    <div class="td-muted"><?= htmlspecialchars($e['dono_email'] ?? '') ?></div>
                </td>
                <td>
                    <span class="badge"><?= htmlspecialchars($planSvc->planoLabel($e['plano'] ?? 'starter')) ?></span>
                    <button type="button" class="btn-ghost btn-sm" style="margin-left:6px" onclick="document.getElementById('plano-modal-<?= $eid ?>').showModal()">
                        Alterar plano
                    </button>
                </td>
                <td>
                    <?php if ($status === 'ativa'): ?>
                    <span class="badge badge-pago">Plano ativo</span>
                    <?php elseif ($status === 'desabilitada'): ?>
                    <span class="badge badge-pendente">Desabilitada</span>
                    <?php else: ?>
                    <span class="badge badge-pendente">Plano inativo</span>
                    <?php endif; ?>
                    <?php if (!(int)($e['ativo'] ?? 1)): ?>
                    <span class="badge" style="margin-left:4px">Loja off</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($e['plano_expira_em'])): ?>
                    <div class="td-muted">Expira: <?= $fmtView($e['plano_expira_em']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($e['trial_ate'])): ?>
                    <div class="td-muted">Trial: <?= $fmtView($e['trial_ate']) ?></div>
                    <?php endif; ?>
                    <?php if (empty($e['plano_expira_em']) && empty($e['trial_ate'])): ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= (int) $e['membros_qtd'] ?></td>
                <td style="white-space:nowrap">
                    <form method="post" action="/superadmin/empresas/plano" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="empresa_id" value="<?= $eid ?>">
                        <button type="submit" class="btn-ghost btn-sm" title="Ativar/desativar plano">
                            <?= (int)($e['plano_ativo'] ?? 1) ? 'Desativar plano' : 'Ativar plano' ?>
                        </button>
                    </form>
                    <form method="post" action="/superadmin/empresas/status" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="empresa_id" value="<?= $eid ?>">
                        <button type="submit" class="btn-ghost btn-sm" title="Habilitar/desabilitar loja">
                            <?= (int)($e['ativo'] ?? 1) ? 'Desabilitar loja' : 'Reativar loja' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <dialog id="plano-modal-<?= $eid ?>" class="sa-plano-dialog">
                <form method="post" action="/superadmin/empresas/plano/alterar" class="sa-plano-form">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="empresa_id" value="<?= $eid ?>">
                    <h3>Alterar plano — <?= htmlspecialchars($e['nome']) ?></h3>
                    <p class="text-muted">Defina o plano, validade e período de trial desta loja.</p>
                    <label class="form-group">
                        <span>Plano</span>
                        <select name="plano" class="input" required>
                            <?php foreach (['starter', 'pro', 'business'] as $p): ?>
                            <option value="<?= $p ?>" <?= ($e['plano'] ?? 'starter') === $p ? 'selected' : '' ?>>
                                <?= htmlspecialchars($planSvc->planoLabel($p)) ?>
                                <?php if (!empty($catalogo[$p]['preco'])): ?> — <?= htmlspecialchars($catalogo[$p]['preco']) ?><?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-group" style="display:flex;align-items:center;gap:8px">
                        <input type="checkbox" name="plano_ativo" value="1" <?= (int)($e['plano_ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>Plano ativo (loja pode usar recursos do plano)</span>
                    </label>
                    <label class="form-group">
                        <span>Expira em (opcional)</span>
                        <input type="datetime-local" name="plano_expira_em" class="input" value="<?= htmlspecialchars($fmtDt($e['plano_expira_em'] ?? null)) ?>">
                    </label>
                    <label class="form-group">
                        <span>Trial até (opcional)</span>
                        <input type="datetime-local" name="trial_ate" class="input" value="<?= htmlspecialchars($fmtDt($e['trial_ate'] ?? null)) ?>">
                    </label>
                    <div class="sa-plano-form__actions">
                        <button type="button" class="btn-ghost" onclick="this.closest('dialog').close()">Cancelar</button>
                        <button type="submit" class="btn-primary">Salvar plano</button>
                    </div>
                </form>
            </dialog>
            <?php endforeach; ?>
            <?php if ($lojas === []): ?>
            <tr><td colspan="7" class="text-muted" style="text-align:center;padding:24px">Nenhuma loja cadastrada.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.sa-plano-dialog { border: none; border-radius: 12px; padding: 0; max-width: 440px; width: calc(100% - 32px); box-shadow: 0 20px 50px rgba(0,0,0,.2); }
.sa-plano-dialog::backdrop { background: rgba(15,23,42,.45); }
.sa-plano-form { padding: 24px; }
.sa-plano-form h3 { margin: 0 0 8px; font-size: 1.1rem; }
.sa-plano-form .form-group { display: block; margin-bottom: 14px; }
.sa-plano-form .form-group > span { display: block; font-size: 0.85rem; margin-bottom: 6px; color: var(--text-muted); }
.sa-plano-form__actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }
</style>
