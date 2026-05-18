<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Services\DashboardService;
use App\Services\NotificationService;

final class DashboardController
{
    public function __construct(private DashboardService $service = new DashboardService()) {}

    public function index(): void
    {
        $empresaId = (int) Session::get('empresa_id');
        $dados = $this->service->dados($empresaId);
        $showOnboarding = !Session::get('onboarding_done') && $empresaId > 0;

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'dados' => $dados,
            'showOnboarding' => $showOnboarding,
        ]);
    }

    public function concluirOnboarding(): void
    {
        Session::set('onboarding_done', true);
        View::redirect('/dashboard');
    }
}
