<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Services\ExportService;
use App\Services\RelatorioService;

final class RelatorioController
{
    public function __construct(
        private RelatorioService $service = new RelatorioService(),
        private ExportService $export = new ExportService(),
    ) {}

    private function periodo(): array
    {
        return [
            'de' => $_GET['de'] ?? date('Y-m-01'),
            'ate' => $_GET['ate'] ?? date('Y-m-t'),
        ];
    }

    public function dre(): void
    {
        $eid = (int) Session::get('empresa_id');
        $p = $this->periodo();
        $dre = $this->service->dre($eid, $p['de'], $p['ate']);

        if (($_GET['formato'] ?? '') === 'xlsx') {
            $this->export->excelDre($dre, "{$p['de']} a {$p['ate']}");
        }
        if (($_GET['formato'] ?? '') === 'pdf') {
            ob_start();
            require \App\Core\App::basePath() . '/src/Views/relatorios/dre_pdf.php';
            $html = ob_get_clean();
            $this->export->pdfHtml($html, 'dre-' . date('Y-m-d'));
        }

        View::render('relatorios/dre', [
            'title' => 'DRE',
            'dre' => $dre,
            'periodo' => $p,
        ]);
    }

    public function fluxo(): void
    {
        $eid = (int) Session::get('empresa_id');
        $p = $this->periodo();
        $dados = $this->service->fluxoCaixa($eid, $p['de'], $p['ate']);
        if (($_GET['formato'] ?? '') === 'xlsx') {
            $this->export->excelGenerico('Fluxo de caixa', ['Período' => $p['de'] . ' a ' . $p['ate']], $dados, 'fluxo');
        }
        View::render('relatorios/fluxo', [
            'title' => 'Fluxo de caixa',
            'dados' => $dados,
            'periodo' => $p,
        ]);
    }

    public function categoria(): void
    {
        $eid = (int) Session::get('empresa_id');
        $p = $this->periodo();
        $tipo = $_GET['tipo'] ?? 'despesa';
        $dados = $this->service->porCategoria($eid, $p['de'], $p['ate'], $tipo);
        if (($_GET['formato'] ?? '') === 'xlsx') {
            $this->export->excelGenerico('Por categoria', ['Tipo' => $tipo, 'Período' => $p['de'] . ' a ' . $p['ate']], $dados, 'categoria');
        }
        View::render('relatorios/categoria', [
            'title' => 'Por categoria',
            'dados' => $dados,
            'tags' => $this->service->porTag($eid, $p['de'], $p['ate']),
            'periodo' => $p,
            'tipo' => $tipo,
        ]);
    }
}
