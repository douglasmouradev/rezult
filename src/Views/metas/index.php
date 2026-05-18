<?php use App\Helpers\Money; ?>
<div class="grid-2">
    <div class="card">
        <div class="card-header" style="margin-bottom:20px;padding:0">
            <div>
                <h3 class="card-title">Nova meta</h3>
                <p class="card-desc">Defina um objetivo financeiro</p>
            </div>
        </div>
        <form method="post" action="/metas">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="form-group">
                <label>Descrição</label>
                <input class="input" name="descricao" required placeholder="Ex: Reserva de emergência">
            </div>
            <div class="form-group">
                <label>Valor alvo</label>
                <input class="input" name="valor_alvo" required placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Prazo</label>
                <input class="input" type="date" name="prazo">
            </div>
            <button type="submit" class="btn-primary">Criar meta</button>
        </form>
    </div>
    <div>
        <?php if (empty($metas)): ?>
        <div class="card empty-state">
            <i class="ph ph-target"></i>
            <p>Nenhuma meta cadastrada.</p>
        </div>
        <?php else: foreach ($metas as $m):
            $pct = $m['valor_alvo'] > 0 ? min(100, ($m['valor_atual'] / $m['valor_alvo']) * 100) : 0;
        ?>
        <div class="card meta-card card-interactive">
            <div class="meta-header">
                <div>
                    <h4 style="font-family:Syne;font-size:1.05rem"><?= htmlspecialchars($m['descricao']) ?></h4>
                    <p class="td-muted" style="margin-top:4px">
                        <?= Money::format((float)$m['valor_atual']) ?> de <?= Money::format((float)$m['valor_alvo']) ?>
                        <?php if ($m['prazo']): ?> · até <?= date('d/m/Y', strtotime($m['prazo'])) ?><?php endif; ?>
                    </p>
                </div>
                <form method="post" action="/metas/<?= $m['id'] ?>/excluir" onsubmit="return confirm('Remover esta meta?')">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <button type="submit" class="btn-ghost btn-sm btn-with-icon btn-action btn-action-danger" title="Excluir meta" data-confirm="Excluir esta meta?">
                        <i class="ph ph-trash" aria-hidden="true"></i><span class="btn-label">Excluir</span>
                    </button>
                </form>
            </div>
            <div class="progress-bar"><span style="width:<?= round($pct) ?>%"></span></div>
            <p class="progress-label"><?= number_format($pct, 0) ?>% atingido</p>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
