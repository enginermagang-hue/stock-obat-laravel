<?php

namespace App\Http\Controllers\Anggaran;

use App\Actions\Anggaran\BuildLaporanAnggaranRealisasi;
use App\Actions\Anggaran\SimpanAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Anggaran;
use App\Models\Facility;
use App\Models\SumberDana;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnggaranController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tahun = $request->integer('tahun') ?: (int) now()->year;
        $facilityFilter = (string) $request->query('facility', '0');
        $sumberDanaId = $request->integer('sumber_dana') ?: null;
        $facilityId = $facilityFilter === 'all' ? null : (int) $facilityFilter;

        $report = app(BuildLaporanAnggaranRealisasi::class)->handle($tahun, $sumberDanaId, $facilityId);
        $editing = $request->integer('edit') ? Anggaran::find($request->integer('edit')) : null;

        if ($request->boolean('partial')) {
            return response()->view('pages.anggaran.partials.summary', [
                'report' => $report,
                'tahun' => $tahun,
            ]);
        }

        return view('pages.anggaran.index', [
            'title' => 'Anggaran & Realisasi',
            'report' => $report,
            'sumberDana' => SumberDana::orderBy('nama')->get(),
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
            'tahun' => $tahun,
            'facilityFilter' => $facilityFilter,
            'sumberDanaId' => $sumberDanaId,
            'editing' => $editing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        $data = $request->validate([
            'tahun' => 'required|integer|min:2000',
            'sumber_dana_id' => 'required|exists:sumber_dana,id',
            'facility_id' => [
                'required',
                'integer',
                'min:0',
                $request->integer('facility_id') > 0
                    ? Rule::exists('facilities', 'id')
                    : Rule::in([Anggaran::RS_FACILITY_ID]),
            ],
            'jumlah_anggaran' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        app(SimpanAnggaran::class)->handle(
            (int) $data['tahun'],
            (int) $data['sumber_dana_id'],
            (int) $data['facility_id'],
            (float) $data['jumlah_anggaran'],
            $data['keterangan'] ?? null,
            allowUpdate: $request->boolean('allow_update'),
        );

        return redirect()->route('anggaran.index', ['tahun' => $data['tahun']])
            ->with('status', 'Pagu anggaran disimpan.');
    }

    public function destroy(Request $request, Anggaran $anggaran): RedirectResponse
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        $anggaran->delete();

        return redirect()->route('anggaran.index', ['tahun' => $anggaran->tahun])
            ->with('status', 'Pagu anggaran dihapus.');
    }
}
