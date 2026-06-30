<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
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
        $showOnboarding = false;
        if ($empresaId > 0) {
            try {
                $stmt = App::pdo()->prepare('SELECT onboarding_concluido FROM empresas WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $empresaId]);
                $showOnboarding = (int) $stmt->fetchColumn() === 0;
            } catch (\Throwable) {
                $showOnboarding = false;
            }
        }

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'dados' => $dados,
            'showOnboarding' => $showOnboarding,
        ]);
    }

    public function concluirOnboarding(): void
    {
        $empresaId = (int) Session::get('empresa_id');
        if ($empresaId > 0) {
            App::pdo()->prepare('UPDATE empresas SET onboarding_concluido = 1 WHERE id = :id')
                ->execute(['id' => $empresaId]);
        }
        View::redirect('/dashboard');
    }
}
