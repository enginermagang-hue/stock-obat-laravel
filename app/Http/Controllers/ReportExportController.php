<?php

namespace App\Http\Controllers;

use App\Services\Laporan\ReportCatalog;
use App\Services\Laporan\ReportExporter;
use App\Services\Laporan\ReportParams;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, string $report, ReportExporter $exporter): Response
    {
        abort_unless(ReportCatalog::canView($request->user(), $report), 403);

        return $exporter->download(
            $report,
            ReportParams::fromRequest($request),
            $request->string('format', 'xlsx')->toString(),
        );
    }
}
