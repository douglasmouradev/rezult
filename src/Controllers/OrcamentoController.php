<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Models\Categoria;
use App\Policies\TenantPolicy;

final class OrcamentoController
{
    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        $mes = $_GET['mes'] ?? date('Y-m');
        $stmt = App::pdo()->prepare(
            'SELECT o.*, c.nome AS categoria_nome, c.cor,
              (SELECT COALESCE(SUM(l.valor),0) FROM lancamentos l
               WHERE l.empresa_id = o.empresa_id AND l.categoria_id = o.categoria_id
               AND l.tipo = c.tipo AND l.status = \'pago\'
               AND DATE_FORMAT(l.data_lancamento, \'%Y-%m\') = o.mes) AS realizado
             FROM orcamentos o
             LEFT JOIN categorias c ON c.id = o.categoria_id
             WHERE o.empresa_id = :e AND o.mes = :m'
        );
        $stmt->execute(['e' => $eid, 'm' => $mes]);
        View::render('orcamentos/index', [
            'title' => 'Orçamento',
            'itens' => $stmt->fetchAll(),
            'mes' => $mes,
            'categorias' => (new Categoria())->findAll($eid, 'nome ASC'),
        ]);
    }

    public function salvar(): void
    {
        $eid = TenantPolicy::empresaId();
        App::pdo()->prepare(
            'INSERT INTO orcamentos (empresa_id, categoria_id, mes, valor_planejado)
             VALUES (:e,:c,:m,:v) ON DUPLICATE KEY UPDATE valor_planejado = VALUES(valor_planejado)'
        )->execute([
            'e' => $eid,
            'c' => (int) $_POST['categoria_id'],
            'm' => $_POST['mes'],
            'v' => Sanitize::money($_POST['valor_planejado']),
        ]);
        Session::flash('success', 'Orçamento salvo.');
        View::redirect('/orcamentos?mes=' . urlencode($_POST['mes']));
    }

    public function excluir(int $id): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        App::pdo()->prepare('DELETE FROM orcamentos WHERE id = :id AND empresa_id = :e')
            ->execute(['id' => $id, 'e' => TenantPolicy::empresaId()]);
        Session::flash('success', 'Linha de orçamento removida.');
        View::redirect('/orcamentos?mes=' . urlencode($_GET['mes'] ?? date('Y-m')));
    }
}
