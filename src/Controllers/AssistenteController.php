<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Services\AssistenteService;

final class AssistenteController
{
    public function index(): void
    {
        View::render('assistente/index', [
            'title' => 'Assistente financeiro',
            'historico' => Session::get('assistente_historico', []),
        ]);
    }

    public function perguntar(): void
    {
        $pergunta = trim($_POST['pergunta'] ?? '');
        if ($pergunta === '') {
            View::redirect('/assistente');
        }
        $eid = (int) Session::get('empresa_id');
        $resposta = (new AssistenteService())->responder($eid, $pergunta);
        $hist = Session::get('assistente_historico', []);
        array_unshift($hist, ['q' => $pergunta, 'a' => $resposta, 't' => date('H:i')]);
        Session::set('assistente_historico', array_slice($hist, 0, 20));

        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') || !empty($_POST['ajax'])) {
            View::json(['resposta' => $resposta]);
        }
        View::redirect('/assistente');
    }
}
