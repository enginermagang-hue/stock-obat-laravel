<?php

namespace App\Http\Controllers\Stok;

use App\Http\Controllers\Controller;
use App\Models\BatchObat;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        $search = trim((string) $request->query('search', ''));
        $expiry = (string) $request->query('expiry', '');

        $batches = BatchObat::query()
            ->with(['obat', 'supplier', 'sumberDana'])
            ->when($search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('no_batch', 'like', '%'.$search.'%')
                ->orWhereHas('obat', fn ($obat) => $obat->where('nama', 'like', '%'.$search.'%'))))
            ->when($expiry === 'expiring', fn ($q) => $q->expiring(90))
            ->when($expiry === 'expired', fn ($q) => $q->whereDate('tanggal_kedaluwarsa', '<', now()))
            ->orderBy('tanggal_kedaluwarsa')
            ->paginate(15)
            ->withQueryString();

        if ($request->boolean('partial')) {
            return response()->view('pages.stok.partials.batch-list', ['batches' => $batches]);
        }

        return view('pages.stok.batch', ['title' => 'Batch Obat', 'batches' => $batches]);
    }
}
