<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Obat;
use App\Services\Laporan\ReportBuilder;
use App\Services\Laporan\ReportCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(Request $request): View|Response
    {
        $catalog = ReportCatalog::forUser($request->user());
        $requested = (string) $request->query('report', 'anggaran-realisasi');
        $report = isset($catalog[$requested]) ? $requested : (array_key_first($catalog) ?? 'anggaran-realisasi');
        $isAudit = $request->routeIs('audit-log.index');

        if ($isAudit && isset($catalog['audit-log'])) {
            $report = 'audit-log';
        }

        $params = [
            'tahun' => $request->integer('tahun') ?: (int) now()->year,
            'obat_id' => $request->integer('obat') ?: null,
            'facility_id' => $request->user()->facility_id ?? ($request->integer('facility') ?: null),
            'from' => $request->query('from') ?: ($isAudit || $report === 'audit-log' ? now()->startOfMonth()->toDateString() : null),
            'to' => $request->query('to') ?: ($isAudit || $report === 'audit-log' ? now()->toDateString() : null),
            'days' => $request->integer('days') ?: 90,
            'event' => $request->query('event') ?: null,
            'search' => $request->query('search') ?: null,
        ];

        $data = app(ReportBuilder::class)->build($report, $params);

        if ($request->boolean('partial')) {
            return response()->view('pages.laporan.partials.list', [
                'data' => $data,
                'params' => $params,
            ]);
        }

        return view('pages.laporan.index', [
            'title' => 'Laporan',
            'catalog' => $catalog,
            'report' => $report,
            'data' => $data,
            'params' => $params,
            'obat' => Obat::orderBy('nama')->get(),
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
        ]);
    }
}
