<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Stock\ReceivePurchaseOrder;
use App\Enums\StatusPurchaseOrder;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('receive-goods'), 403);

        return PurchaseOrderResource::collection(
            PurchaseOrder::with('items')->orderByDesc('id')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('receive-goods'), 403);

        $data = $request->validate([
            'supplier_id' => 'required|exists:supplier,id',
            'sumber_dana_id' => 'required|exists:sumber_dana,id',
            'tanggal_po' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
        ]);

        $po = DB::transaction(function () use ($data, $request) {
            $prefix = 'PO-'.now()->format('Y').'-';
            $last = PurchaseOrder::where('no_po', 'like', $prefix.'%')->orderByDesc('id')->value('no_po');
            $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

            $po = PurchaseOrder::create([
                'no_po' => $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $data['supplier_id'],
                'sumber_dana_id' => $data['sumber_dana_id'],
                'tanggal_po' => $data['tanggal_po'],
                'status' => StatusPurchaseOrder::Draft,
                'user_id' => $request->user()->id,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $subtotal = $item['jumlah'] * $item['harga_beli'];
                $po->items()->create($item + ['subtotal' => $subtotal]);
                $total += $subtotal;
            }
            $po->update(['total' => $total]);

            return $po;
        });

        return (new PurchaseOrderResource($po->load('items')))->response()->setStatusCode(201);
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($request->user()->can('approve-po'), 403);

        $purchaseOrder->update([
            'approved_by' => $request->user()->id,
            'status' => $purchaseOrder->status === StatusPurchaseOrder::Draft
                ? StatusPurchaseOrder::Dikirim
                : $purchaseOrder->status,
        ]);

        return new PurchaseOrderResource($purchaseOrder->load('items'));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, ReceivePurchaseOrder $action)
    {
        abort_unless($request->user()->can('receive-goods'), 403);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.po_item_id' => 'required|exists:purchase_order_item,id',
            'items.*.no_batch' => 'required|string|max:100',
            'items.*.tanggal_kedaluwarsa' => 'required|date',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'nullable|numeric|min:0',
            'items.*.lokasi_rak' => 'nullable|string|max:100',
        ]);

        $receipt = $action->handle(
            $purchaseOrder,
            $data['items'],
            $request->user()->id,
            $request->date('tanggal_terima'),
            $request->string('catatan') ?: null,
        );

        return response()->json(['data' => [
            'id' => $receipt->id,
            'no_penerimaan' => $receipt->no_penerimaan,
            'po_id' => $receipt->po_id,
            'items' => $receipt->items->count(),
        ]], 201);
    }
}
