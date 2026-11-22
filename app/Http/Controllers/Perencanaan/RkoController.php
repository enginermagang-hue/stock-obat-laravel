<?php

namespace App\Http\Controllers\Perencanaan;

use App\Actions\Rko\ApproveRko;
use App\Actions\Rko\BuildRko;
use App\Actions\Rko\GeneratePoFromRko;
use App\Http\Controllers\Controller;
use App\Models\Rko;
use App\Models\SumberDana;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RkoController extends Controller
{
    public function index(Request $request): View
    {
        $rkos = Rko::with('items')->orderByDesc('tahun')->orderByDesc('periode')->orderByDesc('versi')->paginate(10)->withQueryString();
        $selected = $request->integer('select') ? Rko::with(['items.obat', 'supplier', 'sumberDana', 'approver'])->find($request->integer('select')) : null;

        return view('pages.perencanaan.rko', [
            'title' => 'RKO',
            'rkos' => $rkos,
            'selected' => $selected,
            'suppliers' => Supplier::orderBy('nama')->get(),
            'sumberDana' => SumberDana::orderBy('nama')->get(),
        ]);
    }

    public function build(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-rko'), 403);

        $data = $request->validate([
            'tahun' => 'required|integer|min:2000',
            'periode' => 'required|integer|between:1,12',
            'lead_time_bulan' => 'required|integer|min:1|max:12',
            'faktor_pengaman' => 'required|numeric|min:0.5|max:5',
            'sumber_dana_id' => 'nullable|exists:sumber_dana,id',
            'supplier_id' => 'nullable|exists:supplier,id',
            'metode' => 'required|in:historis,ai,kombinasi',
            'bobot_ai' => 'nullable|numeric|min:0|max:1',
            'catatan' => 'nullable|string',
        ]);

        $rko = app(BuildRko::class)->handle($data['tahun'], $data['periode'], [
            'lead_time_bulan' => $data['lead_time_bulan'],
            'faktor_pengaman' => $data['faktor_pengaman'],
            'sumber_dana_id' => $data['sumber_dana_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'metode' => $data['metode'],
            'bobot_ai' => $data['bobot_ai'] ?? 0.5,
            'catatan' => $data['catatan'] ?? null,
        ], $request->user()->id);

        return redirect()->route('rko.index', ['select' => $rko->id])
            ->with('status', 'RKO historis berhasil disusun.');
    }

    public function approve(Request $request, Rko $rko): RedirectResponse
    {
        abort_unless($request->user()->can('manage-rko'), 403);

        app(ApproveRko::class)->handle($rko, $request->user()->id);

        return redirect()->route('rko.index', ['select' => $rko->id])->with('status', 'RKO disetujui.');
    }

    public function generatePo(Request $request, Rko $rko): RedirectResponse
    {
        abort_unless($request->user()->can('approve-po'), 403);

        app(GeneratePoFromRko::class)->handle($rko, $request->user()->id);

        return redirect()->route('pembelian.purchase-order')->with('status', 'Draft PO dibuat dari RKO.');
    }
}
