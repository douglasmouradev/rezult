<?php if (!empty($flash)): ?>
<?php foreach ($flash as $type => $messages): ?>
<?php foreach ($messages as $msg): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($msg) ?>,<?= json_encode($type) ?>));</script>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
