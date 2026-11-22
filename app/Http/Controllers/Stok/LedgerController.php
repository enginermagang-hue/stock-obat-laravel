<?php

namespace App\Http\Controllers\Stok;

use App\Http\Controllers\Controller;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        $search = trim((string) $request->query('search', ''));
        $tipe = (string) $request->query('tipe', '');
        $referensi = (string) $request->query('referensi', '');

        $entries = StockLedger::query()
            ->with(['batch.obat', 'user'])
            ->when($search !== '', fn ($q) => $q->whereHas('batch', fn ($batch) => $batch
                ->where('no_batch', 'like', '%'.$search.'%')
                ->orWhereHas('obat', fn ($obat) => $obat->where('nama', 'like', '%'.$search.'%'))))
            ->when($tipe !== '', fn ($q) => $q->where('tipe', $tipe))
            ->when($referensi !== '', fn ($q) => $q->where('tipe_referensi', $referensi))
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        if ($request->boolean('partial')) {
            return response()->view('pages.stok.partials.ledger-list', ['entries' => $entries]);
        }

        return view('pages.stok.ledger', ['title' => 'Stock Ledger', 'entries' => $entries]);
    }
}
