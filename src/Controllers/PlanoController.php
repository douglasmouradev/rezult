<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Policies\TenantPolicy;
use App\Services\BillingService;

final class PlanoController
{
    public function __construct(private BillingService $billing = new BillingService()) {}

    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        View::render('plano/index', [
            'title' => 'Meu plano',
            'resumo' => $this->billing->resumoEmpresa($eid),
        ]);
    }

    public function upgrade(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $plano = $_POST['plano'] ?? '';
        try {
            $this->billing->solicitarUpgrade(
                TenantPolicy::empresaId(),
                TenantPolicy::usuarioId(),
                $plano,
            );
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        View::redirect('/plano');
    }
}
