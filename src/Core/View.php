<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Csrf;
use App\Helpers\Session;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        extract($data);
        $csrf = Csrf::token();
        $usuario = Session::get('usuario');
        $empresa = Session::get('empresa');
        $empresas = Session::get('empresas', []);
        $flash = Session::pullFlash();
        $appName = App::config('name');
        $appUrl = App::config('url');
        $empresaId = (int) Session::get('empresa_id');
        $podeGerenciar = $empresaId > 0 && \App\Policies\TenantPolicy::podeGerenciarConfig($empresaId);
        $notifCount = 0;
        if ($usuario) {
            try {
                $notifCount = (new \App\Services\NotificationService())->contarNaoLidas((int) Session::get('usuario_id'));
            } catch (\Throwable) {
                $notifCount = 0;
            }
        }

        $viewPath = App::basePath() . '/src/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = App::basePath() . '/src/Views/layouts/' . $layout . '.php';
        require $layoutPath;
    }

    public static function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . App::config('url') . $path);
        exit;
    }
}
