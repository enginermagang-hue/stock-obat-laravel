<?php

namespace App\Livewire\Pembelian;

use App\Actions\Stock\ReceivePurchaseOrder;
use App\Enums\StatusPurchaseOrder;
use App\Models\PurchaseOrder;
use Livewire\Component;

class GoodsReceiptForm extends Component
{
    public ?int $po_id = null;
    public string $tanggal_terima = '';
    public string $catatan = '';
    public array $items = [];

    public function mount(): void
    {
        $this->tanggal_terima = now()->toDateString();

        if ($po = request('po')) {
            $this->po_id = (int) $po;
            $this->loadPo();
        }
    }

    public function updatedPoId(): void
    {
        $this->loadPo();
    }

    public function loadPo(): void
    {
        $this->items = [];

        if (! $this->po_id) {
            return;
        }

        $po = PurchaseOrder::with('items.obat')->find($this->po_id);

        if (! $po || ! in_array($po->status, [StatusPurchaseOrder::Dikirim, StatusPurchaseOrder::DiterimaSebagian], true)) {
            return;
        }

        $this->items = $po->items
            ->filter(fn ($item) => $item->jumlah_diterima < $item->jumlah)
            ->map(fn ($item) => [
                'po_item_id' => $item->id,
                'nama' => $item->obat?->nama,
                'sisa' => $item->jumlah - $item->jumlah_diterima,
                'no_batch' => $item->no_batch ?? '',
                'tanggal_produksi' => '',
                'tanggal_kedaluwarsa' => $item->tanggal_kedaluwarsa?->toDateString() ?? '',
                'jumlah' => $item->jumlah - $item->jumlah_diterima,
                'harga_beli' => (float) $item->harga_beli,
                'lokasi_rak' => '',
            ])
            ->values()
            ->all();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('receive-goods'), 403);

        $po = PurchaseOrder::findOrFail($this->po_id);

        $data = $this->validate([
            'po_id' => 'required|exists:purchase_order,id',
            'tanggal_terima' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.no_batch' => 'required|string|max:100',
            'items.*.tanggal_kedaluwarsa' => 'required|date',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
        ]);

        app(ReceivePurchaseOrder::class)->handle(
            $po,
            $data['items'],
            auth()->id(),
            $data['tanggal_terima'],
            $this->catatan,
        );

        session()->flash('status', 'Penerimaan disimpan dan stok diperbarui.');
        $this->redirectRoute('pembelian.purchase-order', navigate: true);
    }

    public function render()
    {
        return view('livewire.pembelian.goods-receipt-form', [
            'orders' => PurchaseOrder::with('supplier')
                ->whereIn('status', [StatusPurchaseOrder::Dikirim, StatusPurchaseOrder::DiterimaSebagian])
                ->orderByDesc('id')->get(),
        ])->layout('layouts.app', ['title' => 'Penerimaan Barang']);
    }
}
