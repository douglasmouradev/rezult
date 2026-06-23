#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

\App\Core\App::boot();

$n = (new \App\Services\AutomacaoService())->processarVencimentos();
$v = (new \App\Services\CobrancaService())->marcarVencidas();
echo date('c') . " — automações de vencimento: {$n}, cobranças vencidas: {$v}\n";
