#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

\App\Core\App::boot();

$n = (new \App\Services\AutomacaoService())->processarVencimentos();
echo date('c') . " — automações de vencimento: {$n}\n";
