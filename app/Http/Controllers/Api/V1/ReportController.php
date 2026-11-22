<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Laporan\ReportBuilder;
use App\Services\Laporan\ReportCatalog;
use App\Services\Laporan\ReportExporter;
use App\Services\Laporan\ReportParams;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function show(Request $request, string $report, ReportBuilder $builder)
    {
        $this->authorizeReport($request, $report);

        $data = $builder->build($report, ReportParams::fromRequest($request));

        return response()->json([
            'data' => [
                'report' => $data['key'],
                'title' => $data['title'],
                'columns' => array_keys($data['headings']),
                'headings' => array_values($data['headings']),
                'rows' => $data['rows'],
            ],
            'meta' => ['count' => count($data['rows'])],
        ]);
    }

    public function export(Request $request, string $report, ReportExporter $exporter)
    {
        $this->authorizeReport($request, $report);

        return $exporter->download(
            $report,
            ReportParams::fromRequest($request),
            $request->string('format', 'xlsx')->toString(),
        );
    }

    private function authorizeReport(Request $request, string $report): void
    {
        abort_unless(ReportCatalog::canView($request->user(), $report), 403);
    }
}
