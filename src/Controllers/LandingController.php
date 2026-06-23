<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Services\AuthService;

final class LandingController
{
    public function index(): void
    {
        if (Session::get('usuario_id')) {
            View::redirect((new AuthService())->rotaPosLogin());
        }
        View::render('landing/index', ['title' => 'Gestão financeira empresarial'], layout: 'landing');
    }
}
