<?php

namespace App\Livewire\Stok;

use App\Actions\Opname\CreateAdjustmentFromOpname;
use App\Actions\Opname\GenerateOpname;
use App\Actions\Opname\InputStokFisik;
use App\Actions\Opname\VerifyOpname;
use App\Enums\ModeOpname;
use App\Enums\StatusOpname;
use App\Enums\TipeOpname;
use App\Models\StockOpname;
use Livewire\Component;
use Livewire\WithPagination;

class OpnameIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public int $bulan;
    public int $tahun;
    public string $tipe = 'berkala';
    public string $mode = 'buta';
    public string $catatan = '';
    public ?int $selectedId = null;
    public array $fisik = [];

    public function mount(): void
    {
        $this->bulan = (int) now()->month;
        $this->tahun = (int) now()->year;
    }

    public function generate(): void
    {
        abort_unless(auth()->user()->can('input-opname'), 403);

        $data = $this->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
            'tipe' => 'required|in:berkala,mendadak,lokal',
            'mode' => 'required|in:terbuka,buta',
        ]);

        $opname = app(GenerateOpname::class)->handle(
            $data['bulan'],
            $data['tahun'],
            TipeOpname::from($data['tipe']),
            ModeOpname::from($data['mode']),
            null,
            auth()->id(),
            $this->catatan ?: null,
        );

        $this->reset(['showForm', 'catatan']);
        $this->select($opname->id);
        session()->flash('status', 'Sesi opname dibuat.');
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
        $opname = StockOpname::with('items')->findOrFail($id);
        $this->fisik = $opname->items->mapWithKeys(fn ($item) => [
            $item->id => $item->stok_fisik,
        ])->all();
    }

    public function saveFisik(): void
    {
        abort_unless(auth()->user()->can('input-opname'), 403);

        $opname = StockOpname::findOrFail($this->selectedId);
        app(InputStokFisik::class)->handle($opname, $this->fisik);
        $this->select($opname->id);
        session()->flash('status', 'Stok fisik disimpan.');
    }

    public function verify(int $id): void
    {
        abort_unless(auth()->user()->can('verify-opname'), 403);
        app(VerifyOpname::class)->handle(StockOpname::findOrFail($id), auth()->id());
        session()->flash('status', 'Opname diverifikasi.');
    }

    public function approve(int $id): void
    {
        abort_unless(auth()->user()->can('verify-opname'), 403);
        app(CreateAdjustmentFromOpname::class)->handle(StockOpname::findOrFail($id), auth()->id());
        session()->flash('status', 'Opname disetujui dan penyesuaian diproses.');
        $this->select($id);
    }

    public function render()
    {
        $opnames = StockOpname::withCount('items')->orderByDesc('id')->paginate(10);
        $selected = $this->selectedId
            ? StockOpname::with(['items.batch.obat', 'petugas', 'verifier'])->find($this->selectedId)
            : null;

        return view('livewire.stok.opname-index', [
            'opnames' => $opnames,
            'selected' => $selected,
        ])->layout('layouts.app', ['title' => 'Stok Opname']);
    }
}
