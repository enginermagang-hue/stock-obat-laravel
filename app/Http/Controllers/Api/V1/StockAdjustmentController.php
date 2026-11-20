<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Stock\ProcessStockAdjustment;
use App\Enums\StatusPenyesuaian;
use App\Http\Controllers\Controller;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\BatchObat;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-stock'), 403);

        return StockAdjustmentResource::collection(
            StockAdjustment::with('items')->orderByDesc('id')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-stock'), 403);

        $data = $request->validate([
            'tipe' => 'required|in:koreksi,expired,rusak,hilang,retur',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:batch_obat,id',
            'items.*.jumlah' => 'required|integer|not_in:0',
            'items.*.alasan' => 'nullable|string',
        ]);

        $adjustment = DB::transaction(function () use ($data, $request) {
            $prefix = 'ADJ-'.now()->format('Y').'-';
            $last = StockAdjustment::where('no_penyesuaian', 'like', $prefix.'%')->orderByDesc('id')->value('no_penyesuaian');
            $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

            $adjustment = StockAdjustment::create([
                'no_penyesuaian' => $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'tipe' => $data['tipe'],
                'tanggal' => $data['tanggal'],
                'status' => StatusPenyesuaian::Draft,
                'user_id' => $request->user()->id,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $batch = BatchObat::findOrFail($item['batch_id']);
                $adjustment->items()->create([
                    'batch_id' => $batch->id,
                    'obat_id' => $batch->obat_id,
                    'jumlah' => $item['jumlah'],
                    'alasan' => $item['alasan'] ?? null,
                    'nilai' => $item['jumlah'] * (float) $batch->harga_beli,
                ]);
            }

            return $adjustment;
        });

        return (new StockAdjustmentResource($adjustment->load('items')))->response()->setStatusCode(201);
    }

    public function process(Request $request, StockAdjustment $stockAdjustment, ProcessStockAdjustment $action)
    {
        abort_unless($request->user()->can('approve-po'), 403);

        $adjusted = $action->handle($stockAdjustment, $request->user()->id);

        return new StockAdjustmentResource($adjusted);
    }
}
