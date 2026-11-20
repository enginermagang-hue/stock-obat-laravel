<?php

namespace App\Livewire\Stok;

use App\Actions\Stock\ProcessStockAdjustment;
use App\Enums\StatusPenyesuaian;
use App\Enums\TipePenyesuaian;
use App\Models\BatchObat;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AdjustmentIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public string $tipe = 'koreksi';
    public string $tanggal = '';
    public string $catatan = '';
    public array $items = [];

    public function mount(): void
    {
        $this->tanggal = now()->toDateString();
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
        abort_unless(auth()->user()->can('manage-stock'), 403);

        $data = $this->validate([
            'tipe' => 'required|in:koreksi,expired,rusak,hilang,retur',
            'tanggal' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:batch_obat,id',
            'items.*.jumlah' => 'required|integer|not_in:0',
            'items.*.alasan' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data) {
            $adjustment = StockAdjustment::create([
                'no_penyesuaian' => $this->nextNumber(),
                'tipe' => $data['tipe'],
                'tanggal' => $data['tanggal'],
                'status' => StatusPenyesuaian::Draft,
                'user_id' => auth()->id(),
                'catatan' => $this->catatan,
            ]);

            foreach ($data['items'] as $item) {
                $batch = BatchObat::findOrFail($item['batch_id']);
                $adjustment->items()->create([
                    'batch_id' => $batch->id,
                    'obat_id' => $batch->obat_id,
                    'jumlah' => (int) $item['jumlah'],
                    'alasan' => $item['alasan'] ?? null,
                    'nilai' => (int) $item['jumlah'] * (float) $batch->harga_beli,
                ]);
            }
        });

        $this->reset(['showForm', 'catatan']);
        $this->items = [$this->emptyItem()];
        session()->flash('status', 'Penyesuaian dibuat.');
    }

    public function process(int $id): void
    {
        abort_unless(auth()->user()->can('approve-po'), 403);
        app(ProcessStockAdjustment::class)->handle(StockAdjustment::findOrFail($id), auth()->id());
        session()->flash('status', 'Penyesuaian diproses ke ledger.');
    }

    public function cancel(int $id): void
    {
        abort_unless(auth()->user()->can('approve-po'), 403);
        StockAdjustment::findOrFail($id)->update(['status' => StatusPenyesuaian::Dibatalkan]);
    }

    public function render()
    {
        $adjustments = StockAdjustment::withCount('items')->orderByDesc('id')->paginate(10);

        return view('livewire.stok.adjustment-index', [
            'adjustments' => $adjustments,
            'batches' => BatchObat::with('obat')->where('jumlah_sisa', '>', 0)->orderBy('tanggal_kedaluwarsa')->get(),
            'tipeOptions' => TipePenyesuaian::cases(),
        ])->layout('layouts.app', ['title' => 'Penyesuaian Stok']);
    }

    private function emptyItem(): array
    {
        return ['batch_id' => '', 'jumlah' => 0, 'alasan' => ''];
    }

    private function nextNumber(): string
    {
        $prefix = 'ADJ-'.now()->format('Y').'-';
        $last = StockAdjustment::where('no_penyesuaian', 'like', $prefix.'%')->orderByDesc('id')->value('no_penyesuaian');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
