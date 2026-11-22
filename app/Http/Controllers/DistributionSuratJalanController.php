<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DistributionSuratJalanController extends Controller
{
    private const DOKUMEN = [
        'semua' => 'distribusi',
        'surat-jalan' => 'surat-jalan',
        'sbbk' => 'sbbk',
        'bast' => 'bast',
    ];

    public function __invoke(Request $request, Distribution $distribution): Response
    {
        abort_unless($request->user()->can('view-distribution'), 403);

        $dokumen = (string) $request->query('dokumen', 'semua');

        abort_unless(array_key_exists($dokumen, self::DOKUMEN), 404);

        $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch', 'pengirim', 'approver']);

        return Pdf::loadView('pdf.surat-jalan', [
            'distribution' => $distribution,
            'dokumen' => $dokumen,
        ])->stream(self::DOKUMEN[$dokumen].'-'.$distribution->no_surat.'.pdf');
    }
}
