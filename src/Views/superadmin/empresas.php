<div class="page-header">
    <h1>Empresas</h1>
    <p class="text-muted">Todas as empresas cadastradas na plataforma</p>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="card table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Nome</th><th>Plano</th><th>Membros</th><th>Lançamentos</th><th>Criada em</th></tr>
            </thead>
            <tbody>
            <?php foreach ($empresas as $e): ?>
            <tr>
                <td>#<?= (int) $e['id'] ?></td>
                <td><?= htmlspecialchars($e['nome']) ?></td>
                <td><span class="badge"><?= htmlspecialchars($e['plano'] ?? 'starter') ?></span></td>
                <td><?= (int) $e['membros_qtd'] ?></td>
                <td><?= (int) $e['lancamentos_qtd'] ?></td>
                <td><?= date('d/m/Y', strtotime($e['criado_em'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
