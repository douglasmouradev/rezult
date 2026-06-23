<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Middleware\ApiAuthMiddleware;

/** @var Router $router */
$wrap = fn (array $h): callable => fn (...$p) => (new $h[0]())->{$h[1]}(...$p);
$apiAuth = new ApiAuthMiddleware();

$router->get('/api/v1/lancamentos', $wrap([ApiController::class, 'lancamentos']), [$apiAuth]);
$router->post('/api/v1/lancamentos', $wrap([ApiController::class, 'criarLancamento']), [$apiAuth]);
$router->get('/api/v1/contas', $wrap([ApiController::class, 'contas']), [$apiAuth]);
$router->get('/api/v1/categorias', $wrap([ApiController::class, 'categorias']), [$apiAuth]);
$router->get('/api/v1/cobrancas', $wrap([ApiController::class, 'cobrancas']), [$apiAuth]);
