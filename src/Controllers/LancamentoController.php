<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Models\Categoria;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Meta;
use App\Services\ExportService;
use App\Services\LancamentoService;

final class LancamentoController
{
    public function __construct(
        private Lancamento $model = new Lancamento(),
        private LancamentoService $service = new LancamentoService(),
        private Conta $contas = new Conta(),
        private Categoria $categorias = new Categoria(),
        private Meta $metas = new Meta(),
    ) {}

    private function empresaId(): int
    {
        return (int) Session::get('empresa_id');
    }

    public function index(): void
    {
        $eid = $this->empresaId();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filtros = [
            'tipo' => $_GET['tipo'] ?? '',
            'status' => $_GET['status'] ?? '',
            'conta_id' => $_GET['conta_id'] ?? '',
            'categoria_id' => $_GET['categoria_id'] ?? '',
            'centro_custo_id' => $_GET['centro_custo_id'] ?? '',
            'de' => $_GET['de'] ?? '',
            'ate' => $_GET['ate'] ?? '',
            'busca' => $_GET['busca'] ?? '',
            'tag' => $_GET['tag'] ?? '',
        ];

        $centrosStmt = \App\Core\App::pdo()->prepare(
            'SELECT id, nome, codigo FROM centros_custo WHERE empresa_id = :e AND ativo = 1 ORDER BY nome'
        );
        $centrosStmt->execute(['e' => $eid]);

        View::render('lancamentos/index', [
            'title' => 'Lançamentos',
            'resultado' => $this->model->listarFiltrado($eid, $filtros, $page),
            'filtros' => $filtros,
            'contas' => $this->contas->findAll($eid, 'nome ASC'),
            'categorias' => $this->categorias->findAll($eid, 'nome ASC'),
            'centrosCusto' => $centrosStmt->fetchAll(),
        ]);
    }

    public function criarForm(): void
    {
        $this->formView(null);
    }

    public function editarForm(int $id): void
    {
        $this->formView($this->model->find($id, $this->empresaId()));
    }

    private function formView(?array $lancamento): void
    {
        $eid = $this->empresaId();
        $centrosStmt = \App\Core\App::pdo()->prepare(
            'SELECT id, nome, codigo FROM centros_custo WHERE empresa_id = :e AND ativo = 1 ORDER BY nome'
        );
        $centrosStmt->execute(['e' => $eid]);
        View::render('lancamentos/form', [
            'title' => $lancamento ? 'Editar lançamento' : 'Novo lançamento',
            'lancamento' => $lancamento,
            'contas' => $this->contas->findAll($eid, 'nome ASC'),
            'categorias' => $this->categorias->findAll($eid, 'nome ASC'),
            'metas' => $this->metas->findAll($eid, 'descricao ASC'),
            'centrosCusto' => $centrosStmt->fetchAll(),
        ]);
    }

    public function salvar(): void
    {
        $eid = $this->empresaId();
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $this->service->salvar($eid, $_POST, $id);
        Session::flash('success', 'Lançamento salvo.');
        View::redirect('/lancamentos');
    }

    public function toggleStatus(int $id): void
    {
        $eid = $this->empresaId();
        $l = $this->model->find($id, $eid);
        if ($l) {
            $novo = $l['status'] === 'pago' ? 'pendente' : 'pago';
            $this->model->save(['id' => $id, 'status' => $novo], $eid);
            $this->model->invalidarCacheDashboard($eid);
            if (!empty($l['meta_id'])) {
                (new Meta())->atualizarProgresso((int) $l['meta_id'], $eid);
            }
        }
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json')) {
            View::json(['status' => $novo ?? null]);
        }
        View::redirect('/lancamentos');
    }

    public function duplicar(int $id): void
    {
        $eid = $this->empresaId();
        $l = $this->model->find($id, $eid);
        if ($l) {
            unset($l['id'], $l['criado_em']);
            $l['descricao'] .= ' (cópia)';
            unset($l['transferencia_par_id'], $l['anexo_url']);
            $this->service->salvar($eid, $l);
        }
        Session::flash('success', 'Lançamento duplicado.');
        View::redirect('/lancamentos');
    }

    public function excluir(int $id): void
    {
        $eid = $this->empresaId();
        try {
            $this->service->excluir($id, $eid);
            Session::flash('success', 'Lançamento excluído.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        View::redirect('/lancamentos');
    }

    public function importarForm(): void
    {
        View::render('lancamentos/importar', ['title' => 'Importar CSV']);
    }

    public function previewImport(): void
    {
        $eid = $this->empresaId();
        if (empty($_FILES['csv']['tmp_name'])) {
            View::redirect('/lancamentos/importar');
        }
        $preview = $this->service->previewCsv($eid, $_FILES['csv']['tmp_name']);
        $tmp = sys_get_temp_dir() . '/rezult_import_' . bin2hex(random_bytes(8)) . '.csv';
        move_uploaded_file($_FILES['csv']['tmp_name'], $tmp);
        Session::set('import_csv_path', $tmp);
        View::render('lancamentos/importar-preview', [
            'title' => 'Preview importação',
            'preview' => $preview,
        ]);
    }

    public function importar(): void
    {
        $eid = $this->empresaId();
        $path = Session::pull('import_csv_path');
        if ($path && is_file($path)) {
            $n = $this->service->importarCsv($eid, $path);
            unlink($path);
            Session::flash('success', "{$n} lançamentos importados.");
        } elseif (!empty($_FILES['csv']['tmp_name'])) {
            $n = $this->service->importarCsv($eid, $_FILES['csv']['tmp_name']);
            Session::flash('success', "{$n} lançamentos importados.");
        }
        View::redirect('/lancamentos');
    }

    public function aprovar(int $id): void
    {
        $eid = $this->empresaId();
        \App\Policies\TenantPolicy::abortUnlessCanApproveLancamento();
        \App\Core\App::pdo()->prepare(
            "UPDATE lancamentos SET aprovado_por = :u, aprovado_em = NOW(),
             status = CASE WHEN status = 'aguardando_aprovacao' THEN 'pago' ELSE status END
             WHERE id = :id AND empresa_id = :e"
        )->execute(['u' => \App\Policies\TenantPolicy::usuarioId(), 'id' => $id, 'e' => $eid]);
        Session::flash('success', 'Lançamento aprovado.');
        View::redirect('/lancamentos');
    }

    public function exportarCsv(): void
    {
        $eid = $this->empresaId();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lancamentos-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Data', 'Descrição', 'Tipo', 'Valor', 'Status', 'Conta', 'Categoria'], ';');
        $page = 1;
        do {
            $result = $this->model->listarFiltrado($eid, $_GET, $page, 500);
            foreach ($result['items'] as $l) {
                fputcsv($out, [
                    $l['data_lancamento'],
                    $l['descricao'],
                    $l['tipo'],
                    $l['valor'],
                    $l['status'],
                    $l['conta_nome'] ?? '',
                    $l['categoria_nome'] ?? '',
                ], ';');
            }
            $page++;
        } while ($page <= ($result['pages'] ?? 1));
        fclose($out);
        exit;
    }

    public function templateCsv(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo-lancamentos.csv"');
        echo "data;descricao;tipo;valor;status;conta_id;categoria_id\n";
        echo date('Y-m-d') . ";Exemplo receita;receita;100,00;pago;1;1\n";
        exit;
    }
}
