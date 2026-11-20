<?php

namespace App\Livewire\Stok;

use App\Models\BatchObat;
use Livewire\Component;
use Livewire\WithPagination;

class BatchIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $expiry = '';

    public function render()
    {
        $batches = BatchObat::query()
            ->with(['obat', 'supplier', 'sumberDana'])
            ->when($this->search, fn ($q) => $q->where('no_batch', 'like', '%'.$this->search.'%')
                ->orWhereHas('obat', fn ($q2) => $q2->where('nama', 'like', '%'.$this->search.'%')))
            ->when($this->expiry === 'expiring', fn ($q) => $q->expiring(90))
            ->when($this->expiry === 'expired', fn ($q) => $q->whereDate('tanggal_kedaluwarsa', '<', now()))
            ->orderBy('tanggal_kedaluwarsa')
            ->paginate(15);

        return view('livewire.stok.batch-index', ['batches' => $batches])
            ->layout('layouts.app', ['title' => 'Batch Obat']);
    }
}
