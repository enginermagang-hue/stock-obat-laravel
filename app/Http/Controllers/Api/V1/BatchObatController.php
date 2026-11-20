<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatchObatResource;
use App\Models\BatchObat;
use Illuminate\Http\Request;

class BatchObatController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-stock'), 403);

        $batches = BatchObat::with('obat')
            ->when($request->filled('obat_id'), fn ($q) => $q->where('obat_id', $request->integer('obat_id')))
            ->when($request->filled('expiry'), fn ($q) => $q->whereDate('tanggal_kedaluwarsa', '<=', $request->date('expiry')))
            ->orderBy('tanggal_kedaluwarsa')
            ->paginate($request->integer('per_page', 20));

        return BatchObatResource::collection($batches);
    }

    public function show(Request $request, BatchObat $batch)
    {
        abort_unless($request->user()->can('manage-stock'), 403);

        return new BatchObatResource($batch->load('obat'));
    }
}
