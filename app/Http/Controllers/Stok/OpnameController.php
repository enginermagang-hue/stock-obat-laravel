<?php

namespace App\Http\Controllers\Stok;

use App\Actions\Opname\CreateAdjustmentFromOpname;
use App\Actions\Opname\GenerateOpname;
use App\Actions\Opname\InputStokFisik;
use App\Actions\Opname\VerifyOpname;
use App\Enums\ModeOpname;
use App\Enums\TipeOpname;
use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpnameController extends Controller
{
    public function index(Request $request): View
    {
        $opnames = StockOpname::withCount('items')->orderByDesc('id')->paginate(10)->withQueryString();
        $selected = $request->integer('select')
            ? StockOpname::with(['items.batch.obat', 'petugas', 'verifier'])->find($request->integer('select'))
            : null;

        return view('pages.stok.opname', [
            'title' => 'Stok Opname',
            'opnames' => $opnames,
            'selected' => $selected,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('input-opname'), 403);

        $data = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
            'tipe' => 'required|in:berkala,mendadak,lokal',
            'mode' => 'required|in:terbuka,buta',
            'catatan' => 'nullable|string',
        ]);

        $opname = app(GenerateOpname::class)->handle(
            $data['bulan'],
            $data['tahun'],
            TipeOpname::from($data['tipe']),
            ModeOpname::from($data['mode']),
            null,
            $request->user()->id,
            $data['catatan'] ?? null,
        );

        return redirect()
            ->route('stok.opname', ['select' => $opname->id])
            ->with('status', 'Sesi opname dibuat.');
    }

    public function fisik(Request $request, StockOpname $opname): RedirectResponse
    {
        abort_unless($request->user()->can('input-opname'), 403);

        $data = $request->validate([
            'fisik' => 'required|array',
            'fisik.*' => 'nullable|integer|min:0',
        ]);

        app(InputStokFisik::class)->handle($opname, $data['fisik']);

        return redirect()
            ->route('stok.opname', ['select' => $opname->id])
            ->with('status', 'Stok fisik disimpan.');
    }

    public function verify(Request $request, StockOpname $opname): RedirectResponse
    {
        abort_unless($request->user()->can('verify-opname'), 403);

        app(VerifyOpname::class)->handle($opname, $request->user()->id);

        return redirect()->route('stok.opname', ['select' => $opname->id])
            ->with('status', 'Opname diverifikasi.');
    }

    public function approve(Request $request, StockOpname $opname): RedirectResponse
    {
        abort_unless($request->user()->can('verify-opname'), 403);

        app(CreateAdjustmentFromOpname::class)->handle($opname, $request->user()->id);

        return redirect()->route('stok.opname', ['select' => $opname->id])
            ->with('status', 'Opname disetujui dan penyesuaian diproses.');
    }
}
