<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

final class ExportService
{
    public function excelDre(array $dre, string $periodo): void
    {
        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('DRE');
        $ws->setCellValue('A1', 'DRE Simplificado — ' . $periodo);
        $ws->mergeCells('A1:C1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 3;
        $ws->setCellValue("A{$row}", 'RECEITAS');
        $ws->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        foreach ($dre['receitas'] as $r) {
            $ws->setCellValue("A{$row}", $r['nome']);
            $ws->setCellValue("C{$row}", $r['total']);
            $row++;
        }
        $ws->setCellValue("A{$row}", 'Total Receitas');
        $ws->setCellValue("C{$row}", $dre['total_receitas']);
        $ws->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $row += 2;

        $ws->setCellValue("A{$row}", 'DESPESAS');
        $ws->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        foreach ($dre['despesas'] as $d) {
            $ws->setCellValue("A{$row}", $d['nome']);
            $ws->setCellValue("C{$row}", $d['total']);
            $row++;
        }
        $ws->setCellValue("A{$row}", 'Total Despesas');
        $ws->setCellValue("C{$row}", $dre['total_despesas']);
        $ws->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $row += 2;

        $ws->setCellValue("A{$row}", 'RESULTADO');
        $ws->setCellValue("C{$row}", $dre['resultado']);
        $ws->getStyle("A{$row}:C{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
            'font' => ['bold' => true],
        ]);

        foreach (['A', 'B', 'C'] as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="dre-' . date('Y-m-d') . '.xlsx"');
        (new Xlsx($sheet))->save('php://output');
        exit;
    }

    public function csvLancamentos(array $items): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lancamentos-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Data', 'Descrição', 'Tipo', 'Valor', 'Status', 'Conta', 'Categoria'], ';');
        foreach ($items as $l) {
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
        fclose($out);
        exit;
    }

    public function excelGenerico(string $titulo, array $meta, array $rows, string $filename): void
    {
        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setTitle(substr($titulo, 0, 31));
        $ws->setCellValue('A1', $titulo);
        $row = 2;
        foreach ($meta as $k => $v) {
            $ws->setCellValue("A{$row}", $k);
            $ws->setCellValue("B{$row}", (string) $v);
            $row++;
        }
        $row++;
        if (!empty($rows)) {
            $cols = array_keys($rows[0]);
            foreach ($cols as $i => $col) {
                $ws->setCellValue([$i + 1, $row], $col);
            }
            $row++;
            foreach ($rows as $r) {
                foreach ($cols as $i => $col) {
                    $ws->setCellValue([$i + 1, $row], $r[$col] ?? '');
                }
                $row++;
            }
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '-' . date('Y-m-d') . '.xlsx"');
        (new Xlsx($sheet))->save('php://output');
        exit;
    }

    public function pdfHtml(string $html, string $filename): void
    {
        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
        $mpdf->WriteHTML($html);
        $mpdf->Output("{$filename}.pdf", 'D');
        exit;
    }
}
