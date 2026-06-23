<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="grid-2">
<div class="card"><h3>Nova regra</h3>
<form method="post" action="/automacoes"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
<input class="input" name="nome" placeholder="Nome da regra" required style="margin-bottom:8px">
<label class="filter-label"><span>Gatilho</span>
<select name="gatilho" class="input" id="gatilho-select">
    <option value="vencimento">Vencimento próximo</option>
    <option value="import_csv">Após importar CSV</option>
    <option value="descricao_contem">Descrição contém texto</option>
    <option value="recorrente">Ao gerar recorrência</option>
</select></label>
<input class="input" name="texto_condicao" placeholder="Texto na descrição (se aplicável)" style="margin:8px 0">
<label class="filter-label"><span>Ação</span>
<select name="acao" class="input" id="acao-select">
    <option value="notificar">Notificar admin</option>
    <option value="categorizar">Categorizar</option>
    <option value="marcar_pago">Marcar como pago</option>
    <option value="criar_lancamento">Criar lançamento</option>
</select></label>
<div id="acao-categorizar" class="acao-fields">
    <select name="categoria_id" class="input"><?php foreach($categorias as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option><?php endforeach; ?></select>
</div>
<div id="acao-notificar" class="acao-fields">
    <input class="input" name="mensagem" placeholder="Mensagem da notificação" style="margin:8px 0">
</div>
<div id="acao-criar_lancamento" class="acao-fields" style="display:none">
    <select name="conta_id" class="input"><?php foreach($contas as $ct): ?><option value="<?= $ct['id'] ?>"><?= htmlspecialchars($ct['nome']) ?></option><?php endforeach; ?></select>
    <select name="tipo_lanc" class="input"><option value="despesa">Despesa</option><option value="receita">Receita</option></select>
    <input class="input" name="valor_auto" placeholder="Valor">
    <input class="input" name="descricao_auto" placeholder="Descrição do lançamento">
</div>
<button class="btn-primary">Criar regra</button></form></div>
<div class="card"><h3>Regras ativas</h3>
<table><thead><tr><th>Nome</th><th>Gatilho</th><th>Ação</th><th></th></tr></thead><tbody>
<?php foreach ($regras as $r): ?>
<tr><td><?= htmlspecialchars($r['nome']) ?></td><td><?= $r['gatilho'] ?></td><td><?= $r['acao'] ?></td>
<td>
<form method="post" action="/automacoes/<?= $r['id'] ?>/toggle" style="display:inline"><input type="hidden" name="_csrf" value="<?= $csrf ?>"><button class="btn-ghost btn-sm"><?= $r['ativo']?'Desativar':'Ativar' ?></button></form>
<form method="post" action="/automacoes/<?= $r['id'] ?>/excluir" style="display:inline"><input type="hidden" name="_csrf" value="<?= $csrf ?>"><button class="btn-ghost btn-sm">Excluir</button></form>
</td></tr><?php endforeach; ?></tbody></table></div></div>
<script>
document.getElementById('acao-select')?.addEventListener('change', function() {
    document.querySelectorAll('.acao-fields').forEach(el => el.style.display = 'none');
    const id = 'acao-' + this.value;
    const el = document.getElementById(id);
    if (el) el.style.display = 'block';
});
document.getElementById('acao-select')?.dispatchEvent(new Event('change'));
</script>
