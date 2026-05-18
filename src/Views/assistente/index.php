<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="card" style="max-width:720px">
<div class="assistente-chat" style="min-height:320px;margin-bottom:16px">
<?php foreach (array_reverse($historico) as $h): ?>
<div class="chat-q"><strong>Você:</strong> <?= htmlspecialchars($h['q']) ?></div>
<div class="chat-a"><?= nl2br(htmlspecialchars(strip_tags(str_replace(['**'], '', $h['a'])))) ?></div>
<?php endforeach; ?>
</div>
<form method="post" action="/assistente/perguntar" id="assistente-form">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<input type="hidden" name="ajax" value="1">
<input class="input" name="pergunta" placeholder="Ex: Qual meu lucro este mês?" required style="width:calc(100% - 120px)">
<button class="btn-primary">Perguntar</button></form>
<p class="page-subtitle">Sugestões: lucro do mês, saldo, contas a pagar, inadimplência.</p>
</div>
<script>
document.getElementById('assistente-form')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const r = await fetch('/assistente/perguntar', { method:'POST', body: fd, headers: {'Accept':'application/json'} });
  const j = await r.json();
  location.reload();
});
</script>