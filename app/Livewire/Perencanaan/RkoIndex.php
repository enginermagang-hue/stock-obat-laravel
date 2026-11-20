<?php

namespace App\Livewire\Perencanaan;

use App\Actions\Rko\ApproveRko;
use App\Actions\Rko\BuildRko;
use App\Actions\Rko\GeneratePoFromRko;
use App\Enums\StatusRko;
use App\Models\Rko;
use App\Models\SumberDana;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class RkoIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $selectedId = null;
    public int $tahun;
    public int $periode;
    public int $lead_time_bulan = 1;
    public float $faktor_pengaman = 1.0;
    public ?int $sumber_dana_id = null;
    public ?int $supplier_id = null;
    public string $catatan = '';

    public function mount(): void
    {
        $this->tahun = (int) now()->year;
        $this->periode = (int) now()->month;
    }

    public function build(): void
    {
        abort_unless(auth()->user()->can('manage-rko'), 403);

        $data = $this->validate([
            'tahun' => 'required|integer|min:2000',
            'periode' => 'required|integer|between:1,12',
            'lead_time_bulan' => 'required|integer|min:1|max:12',
            'faktor_pengaman' => 'required|numeric|min:0.5|max:5',
            'sumber_dana_id' => 'nullable|exists:sumber_dana,id',
            'supplier_id' => 'nullable|exists:supplier,id',
        ]);

        $rko = app(BuildRko::class)->handle($data['tahun'], $data['periode'], [
            'lead_time_bulan' => $data['lead_time_bulan'],
            'faktor_pengaman' => $data['faktor_pengaman'],
            'sumber_dana_id' => $data['sumber_dana_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'catatan' => $this->catatan ?: null,
        ], auth()->id());

        $this->selectedId = $rko->id;
        $this->reset(['showForm', 'catatan']);
        session()->flash('status', 'RKO historis berhasil disusun.');
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
    }

    public function approve(int $id): void
    {
        abort_unless(auth()->user()->can('manage-rko'), 403);
        app(ApproveRko::class)->handle(Rko::findOrFail($id), auth()->id());
        session()->flash('status', 'RKO disetujui.');
    }

    public function generatePo(int $id): void
    {
        abort_unless(auth()->user()->can('approve-po'), 403);
        app(GeneratePoFromRko::class)->handle(Rko::findOrFail($id), auth()->id());
        session()->flash('status', 'Draft PO dibuat dari RKO.');
        $this->redirectRoute('pembelian.purchase-order', navigate: true);
    }

    public function render()
    {
        $rkos = Rko::with('items')->orderByDesc('tahun')->orderByDesc('periode')->orderByDesc('versi')->paginate(10);
        $selected = $this->selectedId ? Rko::with(['items.obat', 'supplier', 'sumberDana', 'approver'])->find($this->selectedId) : null;

        return view('livewire.perencanaan.rko-index', [
            'rkos' => $rkos,
            'selected' => $selected,
            'suppliers' => Supplier::orderBy('nama')->get(),
            'sumberDana' => SumberDana::orderBy('nama')->get(),
        ])->layout('layouts.app', ['title' => 'RKO']);
    }
}
