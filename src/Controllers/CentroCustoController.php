<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Policies\TenantPolicy;

final class CentroCustoController
{
    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        $stmt = App::pdo()->prepare('SELECT * FROM centros_custo WHERE empresa_id = :e ORDER BY nome');
        $stmt->execute(['e' => $eid]);
        View::render('centros-custo/index', ['title' => 'Centros de custo', 'centros' => $stmt->fetchAll()]);
    }

    public function salvar(): void
    {
        $eid = TenantPolicy::empresaId();
        $nome = Sanitize::raw($_POST['nome']);
        if (!empty($_POST['id'])) {
            App::pdo()->prepare('UPDATE centros_custo SET nome = :n, codigo = :c WHERE id = :id AND empresa_id = :e')
                ->execute(['n' => $nome, 'c' => Sanitize::raw($_POST['codigo'] ?? ''), 'id' => (int) $_POST['id'], 'e' => $eid]);
        } else {
            App::pdo()->prepare('INSERT INTO centros_custo (empresa_id, nome, codigo) VALUES (:e,:n,:c)')
                ->execute(['e' => $eid, 'n' => $nome, 'c' => Sanitize::raw($_POST['codigo'] ?? '')]);
        }
        Session::flash('success', 'Centro de custo salvo.');
        View::redirect('/centros-custo');
    }
}
