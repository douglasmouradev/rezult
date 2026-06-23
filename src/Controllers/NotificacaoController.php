<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Policies\TenantPolicy;
use App\Services\NotificationService;

final class NotificacaoController
{
    public function __construct(private NotificationService $notif = new NotificationService()) {}

    public function index(): void
    {
        $todas = ($_GET['filtro'] ?? '') === 'todas';
        View::render('notificacoes/index', [
            'title' => 'Notificações',
            'notificacoes' => $todas
                ? $this->notif->listarTodas(TenantPolicy::usuarioId(), 100)
                : $this->notif->listarNaoLidas(TenantPolicy::usuarioId(), 50),
            'filtroTodas' => $todas,
        ]);
    }

    public function marcarLida(int $id): void
    {
        $this->notif->marcarLida($id, TenantPolicy::usuarioId());
        View::redirect('/notificacoes');
    }

    public function marcarTodas(): void
    {
        $this->notif->marcarTodasLidas(TenantPolicy::usuarioId());
        View::redirect('/notificacoes');
    }
}
