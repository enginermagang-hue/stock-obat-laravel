<?php

namespace App\Services\Laporan;

use App\Exports\ArrayReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportExporter
{
    public function __construct(private readonly ReportBuilder $builder) {}

    public function download(string $report, array $params, string $format = 'xlsx'): Response
    {
        $data = $this->builder->build($report, $params);
        $filename = 'laporan-'.$report.'-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            return Pdf::loadView('pdf.laporan', [
                'report' => $data,
                'params' => $params,
                'generatedAt' => now(),
            ])->download($filename.'.pdf');
        }

        $headings = array_values($data['headings']);
        $rows = array_map(
            fn (array $row) => array_map(fn (string $key) => $row[$key] ?? null, array_keys($data['headings'])),
            $data['rows'],
        );

        return Excel::download(
            new ArrayReportExport($headings, $rows, $data['title']),
            $filename.'.xlsx',
        );
    }
}
