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

    public function sitemap(): void
    {
        $base = rtrim((string) \App\Core\App::config('url'), '/');
        $paths = ['/', '/login', '/cadastro', '/privacidade', '/termos'];
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($paths as $path) {
            $loc = htmlspecialchars($base . $path, ENT_XML1, 'UTF-8');
            $priority = $path === '/' ? '1.0' : '0.5';
            echo "  <url><loc>{$loc}</loc><changefreq>weekly</changefreq><priority>{$priority}</priority></url>\n";
        }
        echo '</urlset>';
    }
}
