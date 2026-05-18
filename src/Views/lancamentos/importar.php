<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="card" style="max-width:520px">
    <p class="text-muted">Colunas: data;descricao;tipo;valor;status;conta_id;categoria_id</p>
    <p><a href="/lancamentos/template-csv" class="btn btn-secondary btn-sm">Baixar modelo CSV</a></p>
    <form method="post" action="/lancamentos/importar/preview" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <input type="file" name="csv" accept=".csv" required class="input">
        <button type="submit" class="btn-primary" style="margin-top:16px">Importar</button>
    </form>
</div>
