<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockLedgerResource;
use App\Models\StockLedger;
use Illuminate\Http\Request;

class StockLedgerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-stock'), 403);

        $entries = StockLedger::query()
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->integer('batch_id')))
            ->when($request->filled('tipe'), fn ($q) => $q->where('tipe', $request->string('tipe')))
            ->when($request->filled('tipe_referensi'), fn ($q) => $q->where('tipe_referensi', $request->string('tipe_referensi')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('tanggal_transaksi', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('tanggal_transaksi', '<=', $request->date('to')))
            ->orderByDesc('tanggal_transaksi')
            ->paginate($request->integer('per_page', 50));

        return StockLedgerResource::collection($entries);
    }
}
