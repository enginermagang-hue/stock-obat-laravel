<?php

namespace App\Livewire\Stok;

use App\Models\StockLedger;
use Livewire\Component;
use Livewire\WithPagination;

class LedgerIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $tipe = '';
    public string $referensi = '';

    public function render()
    {
        $entries = StockLedger::query()
            ->with(['batch.obat', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('batch', fn ($q2) => $q2
                ->where('no_batch', 'like', '%'.$this->search.'%')
                ->orWhereHas('obat', fn ($q3) => $q3->where('nama', 'like', '%'.$this->search.'%'))))
            ->when($this->tipe, fn ($q) => $q->where('tipe', $this->tipe))
            ->when($this->referensi, fn ($q) => $q->where('tipe_referensi', $this->referensi))
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.stok.ledger-index', ['entries' => $entries])
            ->layout('layouts.app', ['title' => 'Stock Ledger']);
    }
}
