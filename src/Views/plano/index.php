<?php require __DIR__ . '/../partials/flash.php'; ?>
<?php $r = $resumo; $plan = new \App\Services\PlanService(); ?>
<div class="page-header">
    <h1>Meu plano</h1>
    <p class="text-muted">Plano atual, limites e upgrade da sua loja</p>
</div>

<div class="grid-2 plano-grid">
    <div class="card">
        <h3>Plano atual: <strong><?= htmlspecialchars($r['plano_label'] ?? 'Starter') ?></strong></h3>
        <?php if (!empty($r['bloqueio'])): ?>
        <p class="text-muted" style="color:var(--red)"><?= htmlspecialchars($r['bloqueio']) ?></p>
        <?php endif; ?>
        <?php if (!empty($r['plano_expira_em'])): ?>
        <p class="text-muted">Expira em <?= date('d/m/Y H:i', strtotime((string) $r['plano_expira_em'])) ?></p>
        <?php endif; ?>
        <ul style="margin:16px 0;padding-left:20px">
            <li>Empresas (dono): <?= $r['limites']['empresas'] ?? '∞' ?></li>
            <li>Usuários por loja: <?= $r['limites']['usuarios'] ?? '∞' ?></li>
            <li>Tokens API: <?= $r['limites']['api_tokens'] ?? '∞' ?></li>
            <li>Webhooks: <?= $r['limites']['webhooks'] ?? '∞' ?></li>
        </ul>
        <h4>Recursos incluídos</h4>
        <ul style="padding-left:20px">
            <?php foreach ($r['features'] ?? [] as $f): ?>
            <li><?= htmlspecialchars($plan->labelFeature($f)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h3>Fazer upgrade</h3>
        <p class="text-muted">Solicite um plano superior. Nossa equipe processará o upgrade e entrará em contato.</p>
        <?php foreach (['pro', 'business'] as $p):
            if (($r['plano'] ?? 'starter') === $p) {
                continue;
            }
            $cat = $r['planos_disponiveis'][$p] ?? [];
        ?>
        <form method="post" action="/plano/upgrade" class="mb-2" style="margin-bottom:16px;padding:12px;border:1px solid var(--border);border-radius:8px">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="plano" value="<?= $p ?>">
            <strong><?= htmlspecialchars($cat['nome'] ?? $p) ?></strong>
            <p class="text-muted" style="font-size:0.85rem;margin:8px 0"><?= htmlspecialchars($cat['preco'] ?? '') ?></p>
            <button type="submit" class="btn-primary btn-sm">Solicitar <?= htmlspecialchars($cat['nome'] ?? $p) ?></button>
        </form>
        <?php endforeach; ?>
        <a href="/empresas" class="btn btn-ghost btn-sm">Gerenciar empresas</a>
    </div>
</div>
