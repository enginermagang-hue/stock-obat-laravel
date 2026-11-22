<?php

namespace App\Http\Controllers\Prediksi;

use App\Actions\Prediksi\EvaluasiAkurasi;
use App\Actions\Prediksi\PrediksiKebutuhanObat;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\PrediksiObat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrediksiController extends Controller
{
    public function index(Request $request): View
    {
        $next = now()->addMonthNoOverflow();
        $bulan = $request->integer('bulan') ?: (int) $next->month;
        $tahun = $request->integer('tahun') ?: (int) $next->year;

        $predictions = PrediksiObat::with(['obat', 'facility'])
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->orderBy('obat_id')
            ->paginate(15)
            ->withQueryString();

        $summary = PrediksiObat::query()
            ->where('periode_tahun', $tahun)
            ->where('periode_bulan', $bulan)
            ->selectRaw('count(*) as total, avg(mape) as avg_mape, sum(case when confidence_score < 0.6 then 1 else 0 end) as low_confidence')
            ->first();

        return view('pages.prediksi.index', [
            'title' => 'Prediksi Kebutuhan',
            'predictions' => $predictions,
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
            'summary' => $summary,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-prediksi'), 403);

        $data = $request->validate([
            'periode_bulan' => 'required|integer|between:1,12',
            'periode_tahun' => 'required|integer|min:2000',
            'facility_id' => 'nullable|exists:facilities,id',
        ]);

        $facilityId = $request->user()->facility_id ?: ($data['facility_id'] ?? null);

        $results = app(PrediksiKebutuhanObat::class)->handle(
            $data['periode_bulan'],
            $data['periode_tahun'],
            $facilityId,
            $request->user()->id,
        );

        return redirect()->route('prediksi.index', [
            'bulan' => $data['periode_bulan'],
            'tahun' => $data['periode_tahun'],
        ])->with('status', $results->count().' prediksi dihitung. Tinjau dan validasi sebelum dipakai di RKO.');
    }

    public function validate(Request $request, PrediksiObat $prediksi): RedirectResponse
    {
        abort_unless($request->user()->can('manage-prediksi'), 403);

        app(EvaluasiAkurasi::class)->handle($prediksi);

        return back()->with('status', 'Prediksi divalidasi dan siap dipakai di RKO.');
    }
}
