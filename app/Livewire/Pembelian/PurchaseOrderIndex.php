<?php

namespace App\Livewire\Pembelian;

use App\Enums\StatusPurchaseOrder;
use App\Models\Obat;
use App\Models\PurchaseOrder;
use App\Models\SumberDana;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $supplier_id = null;
    public ?int $sumber_dana_id = null;
    public string $tanggal_po = '';
    public string $catatan = '';
    public array $items = [];

    public function mount(): void
    {
        $this->tanggal_po = now()->toDateString();
        $this->items = [$this->emptyItem()];
    }

    public function addItem(): void
    {
        $this->items[] = $this->emptyItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('receive-goods'), 403);

        $data = $this->validate([
            'supplier_id' => 'required|exists:supplier,id',
            'sumber_dana_id' => 'required|exists:sumber_dana,id',
            'tanggal_po' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'no_po' => $this->nextNumber(),
                'supplier_id' => $data['supplier_id'],
                'sumber_dana_id' => $data['sumber_dana_id'],
                'tanggal_po' => $data['tanggal_po'],
                'status' => StatusPurchaseOrder::Draft,
                'total' => 0,
                'user_id' => auth()->id(),
                'catatan' => $this->catatan,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $subtotal = $item['jumlah'] * $item['harga_beli'];
                $po->items()->create([
                    'obat_id' => $item['obat_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_beli' => $item['harga_beli'],
                    'subtotal' => $subtotal,
                ]);
                $total += $subtotal;
            }
            $po->update(['total' => $total]);
        });

        $this->reset(['showForm', 'supplier_id', 'sumber_dana_id', 'catatan']);
        $this->items = [$this->emptyItem()];
        session()->flash('status', 'Purchase Order dibuat.');
    }

    public function send(int $id): void
    {
        abort_unless(auth()->user()->can('receive-goods'), 403);
        $po = PurchaseOrder::findOrFail($id);
        if ($po->status === StatusPurchaseOrder::Draft) {
            $po->update(['status' => StatusPurchaseOrder::Dikirim]);
        }
    }

    public function approve(int $id): void
    {
        abort_unless(auth()->user()->can('approve-po'), 403);
        $po = PurchaseOrder::findOrFail($id);
        $po->update([
            'approved_by' => auth()->id(),
            'status' => $po->status === StatusPurchaseOrder::Draft ? StatusPurchaseOrder::Dikirim : $po->status,
        ]);
    }

    public function cancel(int $id): void
    {
        abort_unless(auth()->user()->can('approve-po'), 403);
        PurchaseOrder::findOrFail($id)->update(['status' => StatusPurchaseOrder::Dibatalkan]);
    }

    public function render()
    {
        $orders = PurchaseOrder::with(['supplier', 'sumberDana'])->orderByDesc('id')->paginate(10);

        return view('livewire.pembelian.purchase-order-index', [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('nama')->get(),
            'sumberDana' => SumberDana::orderBy('nama')->get(),
            'obat' => Obat::orderBy('nama')->get(),
        ])->layout('layouts.app', ['title' => 'Purchase Order']);
    }

    private function emptyItem(): array
    {
        return ['obat_id' => '', 'jumlah' => 1, 'harga_beli' => 0];
    }

    private function nextNumber(): string
    {
        $prefix = 'PO-'.now()->format('Y').'-';
        $last = PurchaseOrder::where('no_po', 'like', $prefix.'%')->orderByDesc('id')->value('no_po');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
