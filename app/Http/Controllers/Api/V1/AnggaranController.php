<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Anggaran\BuildLaporanAnggaranRealisasi;
use App\Actions\Anggaran\SimpanAnggaran;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnggaranResource;
use App\Models\Anggaran;
use Illuminate\Http\Request;

class AnggaranController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        $tahun = $request->integer('tahun', (int) now()->year);

        return AnggaranResource::collection(
            Anggaran::with('sumberDana')
                ->where('tahun', $tahun)
                ->when($request->filled('sumber_dana_id'), fn ($query) => $query->where('sumber_dana_id', $request->integer('sumber_dana_id')))
                ->when($request->filled('facility_id'), fn ($query) => $query->where('facility_id', $request->integer('facility_id')))
                ->orderBy('sumber_dana_id')
                ->orderBy('facility_id')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function store(Request $request, SimpanAnggaran $action)
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        $data = $request->validate([
            'sumber_dana_id' => 'required|exists:sumber_dana,id',
            'tahun' => 'required|integer|min:2000',
            'facility_id' => 'required|integer|min:0',
            'jumlah_anggaran' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        if ($data['facility_id'] > 0) {
            $request->validate(['facility_id' => 'exists:facilities,id']);
        }

        $anggaran = $action->handle(
            $data['tahun'],
            $data['sumber_dana_id'],
            $data['facility_id'],
            (float) $data['jumlah_anggaran'],
            $data['keterangan'] ?? null,
            allowUpdate: false,
        );

        return (new AnggaranResource($anggaran->load('sumberDana')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Anggaran $anggaran)
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        return new AnggaranResource($anggaran->load('sumberDana'));
    }

    public function update(Request $request, Anggaran $anggaran, SimpanAnggaran $action)
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        $data = $request->validate([
            'jumlah_anggaran' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $anggaran = $action->handle(
            $anggaran->tahun,
            $anggaran->sumber_dana_id,
            $anggaran->facility_id,
            (float) $data['jumlah_anggaran'],
            $data['keterangan'] ?? null,
        );

        return new AnggaranResource($anggaran->load('sumberDana'));
    }

    public function destroy(Request $request, Anggaran $anggaran)
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        $anggaran->delete();

        return response()->noContent();
    }

    public function rekap(Request $request, BuildLaporanAnggaranRealisasi $action)
    {
        abort_unless($request->user()->can('manage-anggaran'), 403);

        $tahun = $request->integer('tahun', (int) now()->year);
        $facilityId = $request->filled('facility_id') ? $request->integer('facility_id') : null;

        $report = $action->handle(
            $tahun,
            $request->filled('sumber_dana_id') ? $request->integer('sumber_dana_id') : null,
            $facilityId,
        );

        return response()->json([
            'data' => $report['rows']->map(fn (array $row) => [
                'anggaran' => new AnggaranResource($row['anggaran']),
                'pagu' => $row['pagu'],
                'komitmen_bersih' => $row['komitmen_bersih'],
                'realisasi' => $row['realisasi'],
                'sisa' => $row['sisa'],
                'persen_realisasi' => $row['persen_realisasi'],
                'persen_komitmen' => $row['persen_komitmen'],
            ])->values(),
            'summary' => [
                'pagu' => $report['pagu'],
                'komitmen' => $report['komitmen'],
                'realisasi' => $report['realisasi'],
                'sisa' => $report['sisa'],
            ],
        ]);
    }
}
