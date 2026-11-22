<?php

namespace App\Http\Controllers\Pembelian;

use App\Actions\Stock\ReceivePurchaseOrder;
use App\Enums\StatusPurchaseOrder;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\SumberDana;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function index(Request $request): View|Response
    {
        $search = trim((string) $request->query('search', ''));
        $supplierId = $request->integer('supplier') ?: null;
        $sumberDanaId = $request->integer('sumber_dana') ?: null;
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $receipts = GoodsReceipt::with(['purchaseOrder', 'supplier', 'sumberDana', 'user'])
            ->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('no_penerimaan', 'like', '%'.$search.'%')
                ->orWhereHas('purchaseOrder', fn ($po) => $po->where('no_po', 'like', '%'.$search.'%'))
                ->orWhereHas('supplier', fn ($supplier) => $supplier->where('nama', 'like', '%'.$search.'%'))))
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($sumberDanaId, fn ($query) => $query->where('sumber_dana_id', $sumberDanaId))
            ->when($from !== '', fn ($query) => $query->whereDate('tanggal_terima', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('tanggal_terima', '<=', $to))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->boolean('partial')) {
            return response()->view('pages.pembelian.goods-receipt.partials.list', ['receipts' => $receipts]);
        }

        return view('pages.pembelian.goods-receipt.index', [
            'title' => 'Penerimaan Barang',
            'receipts' => $receipts,
            'suppliers' => Supplier::orderBy('nama')->get(),
            'sumberDana' => SumberDana::orderBy('nama')->get(),
            'filters' => [
                'search' => $search,
                'supplier' => $supplierId,
                'sumber_dana' => $sumberDanaId,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $poId = $request->integer('po');
        $selected = $poId ? PurchaseOrder::with('items.obat')->find($poId) : null;
        $items = [];

        if ($selected && in_array($selected->status, [StatusPurchaseOrder::Dikirim, StatusPurchaseOrder::DiterimaSebagian], true)) {
            $items = $selected->items
                ->filter(fn ($item) => $item->jumlah_diterima < $item->jumlah)
                ->map(fn ($item) => [
                    'po_item_id' => $item->id,
                    'nama' => $item->obat?->nama,
                    'sisa' => $item->jumlah - $item->jumlah_diterima,
                    'no_batch' => $item->no_batch ?? '',
                    'tanggal_kedaluwarsa' => $item->tanggal_kedaluwarsa?->toDateString() ?? '',
                    'jumlah' => $item->jumlah - $item->jumlah_diterima,
                    'harga_beli' => (float) $item->harga_beli,
                    'lokasi_rak' => '',
                ])
                ->values()
                ->all();
        }

        return view('pages.pembelian.goods-receipt.form', [
            'title' => 'Buat Penerimaan',
            'receipt' => null,
            'orders' => PurchaseOrder::with('supplier')
                ->whereIn('status', [StatusPurchaseOrder::Dikirim, StatusPurchaseOrder::DiterimaSebagian])
                ->orderByDesc('id')->get(),
            'selected' => $selected,
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('receive-goods'), 403);

        $data = $request->validate([
            'po_id' => 'required|exists:purchase_order,id',
            'tanggal_terima' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.po_item_id' => 'required|exists:purchase_order_item,id',
            'items.*.no_batch' => 'required|string|max:100',
            'items.*.tanggal_kedaluwarsa' => 'required|date',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
            'items.*.lokasi_rak' => 'nullable|string|max:100',
        ]);

        $po = PurchaseOrder::findOrFail($data['po_id']);

        $receipt = app(ReceivePurchaseOrder::class)->handle(
            $po,
            $data['items'],
            $request->user()->id,
            Carbon::parse($data['tanggal_terima']),
            $data['catatan'] ?? null,
        );

        return redirect()->route('pembelian.penerimaan.show', $receipt)
            ->with('status', 'Penerimaan disimpan dan stok diperbarui.');
    }

    public function show(GoodsReceipt $penerimaan): View
    {
        $penerimaan->load(['purchaseOrder', 'supplier', 'sumberDana', 'user', 'items.obat']);

        return view('pages.pembelian.goods-receipt.show', [
            'title' => 'Detail Penerimaan '.$penerimaan->no_penerimaan,
            'receipt' => $penerimaan,
        ]);
    }

    public function edit(GoodsReceipt $penerimaan): View
    {
        $penerimaan->load('items.obat');

        return view('pages.pembelian.goods-receipt.form', [
            'title' => 'Ubah Penerimaan '.$penerimaan->no_penerimaan,
            'receipt' => $penerimaan,
            'orders' => collect(),
            'selected' => $penerimaan->purchaseOrder,
            'items' => $penerimaan->items->map(fn ($item) => [
                'po_item_id' => $item->po_item_id,
                'nama' => $item->obat?->nama,
                'sisa' => null,
                'no_batch' => $item->no_batch,
                'tanggal_kedaluwarsa' => $item->tanggal_kedaluwarsa?->toDateString() ?? '',
                'jumlah' => $item->jumlah,
                'harga_beli' => (float) $item->harga_beli,
                'lokasi_rak' => $item->lokasi_rak ?? '',
            ])->values()->all(),
        ]);
    }

    public function update(Request $request, GoodsReceipt $penerimaan): RedirectResponse
    {
        abort_unless($request->user()->can('receive-goods'), 403);

        // Hanya field non-kuantitas yang boleh berubah; stok & ledger sudah tercatat.
        $data = $request->validate([
            'tanggal_terima' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.lokasi_rak' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data, $penerimaan) {
            $penerimaan->update([
                'tanggal_terima' => $data['tanggal_terima'],
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($penerimaan->items()->orderBy('id')->get() as $index => $item) {
                $item->update([
                    'lokasi_rak' => $data['items'][$index]['lokasi_rak'] ?? null,
                ]);
            }
        });

        activity('pembelian')
            ->causedBy($request->user())
            ->performedOn($penerimaan)
            ->log('Penerimaan barang diperbarui');

        return redirect()->route('pembelian.penerimaan.show', $penerimaan)
            ->with('status', 'Penerimaan diperbarui.');
    }
}
