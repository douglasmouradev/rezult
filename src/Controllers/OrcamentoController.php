<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Models\Categoria;
use App\Policies\TenantPolicy;
use App\Services\OrcamentoService;

final class OrcamentoController
{
    public function __construct(private OrcamentoService $service = new OrcamentoService()) {}

    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        $mes = (string) ($_GET['mes'] ?? date('Y-m'));

        try {
            $itens = $this->service->listarPorMes($eid, $mes);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $itens = [];
        }

        View::render('orcamentos/index', [
            'title' => 'Orçamento',
            'itens' => $itens,
            'mes' => preg_match('/^\d{4}-\d{2}$/', $mes) ? $mes : date('Y-m'),
            'categorias' => (new Categoria())->findAll($eid, 'nome ASC'),
        ]);
    }

    public function salvar(): void
    {
        $eid = TenantPolicy::empresaId();
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $mes = (string) ($_POST['mes'] ?? date('Y-m'));

        if ($categoriaId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
            Session::flash('error', 'Categoria e mês são obrigatórios.');
            View::redirect('/orcamentos');
        }

        if (!TenantPolicy::categoriaDaEmpresa($categoriaId, $eid)) {
            Session::flash('error', 'Categoria inválida.');
            View::redirect('/orcamentos?mes=' . urlencode($mes));
        }

        App::pdo()->prepare(
            'INSERT INTO orcamentos (empresa_id, categoria_id, mes, valor_planejado)
             VALUES (:e,:c,:m,:v) ON DUPLICATE KEY UPDATE valor_planejado = VALUES(valor_planejado)'
        )->execute([
            'e' => $eid,
            'c' => $categoriaId,
            'm' => $mes,
            'v' => Sanitize::money($_POST['valor_planejado'] ?? '0'),
        ]);
        Session::flash('success', 'Orçamento salvo.');
        View::redirect('/orcamentos?mes=' . urlencode($mes));
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
