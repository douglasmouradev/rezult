<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="grid-2">
<div class="card"><h3>Nova regra</h3>
<form method="post" action="/automacoes"><input type="hidden" name="_csrf" value="<?= $csrf ?>">
<input class="input" name="nome" placeholder="Nome da regra" required style="margin-bottom:8px">
<select name="gatilho" class="input"><option value="vencimento">Vencimento próximo</option><option value="import_csv">Após importar CSV</option><option value="descricao_contem">Descrição contém texto</option><option value="recorrente">Recorrente</option></select>
<input class="input" name="texto_condicao" placeholder="Texto na descrição (se aplicável)" style="margin:8px 0">
<select name="acao" class="input"><option value="notificar">Notificar admin</option><option value="categorizar">Categorizar</option></select>
<select name="categoria_id" class="input"><?php foreach($categorias as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option><?php endforeach; ?></select>
<input class="input" name="mensagem" placeholder="Mensagem da notificação" style="margin:8px 0">
<button class="btn-primary">Criar regra</button></form></div>
<div class="card"><h3>Regras ativas</h3>
<table><thead><tr><th>Nome</th><th>Gatilho</th><th>Ação</th><th></th></tr></thead><tbody>
<?php foreach ($regras as $r): ?>
<tr><td><?= htmlspecialchars($r['nome']) ?></td><td><?= $r['gatilho'] ?></td><td><?= $r['acao'] ?></td>
<td>
<form method="post" action="/automacoes/<?= $r['id'] ?>/toggle" style="display:inline"><input type="hidden" name="_csrf" value="<?= $csrf ?>"><button class="btn-ghost btn-sm"><?= $r['ativo']?'Desativar':'Ativar' ?></button></form>
<form method="post" action="/automacoes/<?= $r['id'] ?>/excluir" style="display:inline"><input type="hidden" name="_csrf" value="<?= $csrf ?>"><button class="btn-ghost btn-sm">Excluir</button></form>
</td></tr><?php endforeach; ?></tbody></table></div></div>