<?php

namespace App\Livewire\Perencanaan;

use App\Actions\Permintaan\CreatePermintaanMandiri;
use App\Actions\Permintaan\SubmitPermintaan;
use App\Actions\Permintaan\VerifyPermintaan;
use App\Enums\StatusPermintaan;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\PermintaanObat;
use Livewire\Component;
use Livewire\WithPagination;

class PermintaanIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $selectedId = null;
    public ?int $facility_id = null;
    public string $periode_awal = '';
    public string $periode_akhir = '';
    public array $items = [];
    public array $decisions = [];
    public string $catatan_verifikasi = '';

    public function mount(): void
    {
        $this->facility_id = auth()->user()->facility_id;
        $this->periode_awal = now()->startOfMonth()->toDateString();
        $this->periode_akhir = now()->endOfMonth()->toDateString();
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
        abort_unless(auth()->user()->can('create-permintaan'), 403);

        $data = $this->validate([
            'facility_id' => 'required|exists:facilities,id',
            'periode_awal' => 'nullable|date',
            'periode_akhir' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.jumlah_diminta' => 'required|integer|min:1',
        ]);

        $facilityId = auth()->user()->facility_id ?: (int) $data['facility_id'];

        $permintaan = app(CreatePermintaanMandiri::class)->handle(
            $facilityId,
            $data['items'],
            $this->periode_awal ?: null,
            $this->periode_akhir ?: null,
            auth()->id(),
        );

        $this->selectedId = $permintaan->id;
        $this->reset(['showForm']);
        $this->items = [$this->emptyItem()];
        session()->flash('status', 'Permintaan draft dibuat.');
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
        $permintaan = PermintaanObat::with('items')->findOrFail($id);
        $this->decisions = $permintaan->items->mapWithKeys(fn ($item) => [
            $item->id => [
                'jumlah_disetujui' => $item->jumlah_disetujui ?? $item->jumlah_diminta,
                'status_item' => $item->status_item?->value ?? '',
                'keterangan' => $item->keterangan,
            ],
        ])->all();
    }

    public function submit(int $id): void
    {
        abort_unless(auth()->user()->can('create-permintaan'), 403);
        app(SubmitPermintaan::class)->handle(PermintaanObat::findOrFail($id));
        session()->flash('status', 'Permintaan dikirim ke apoteker.');
    }

    public function verify(int $id): void
    {
        abort_unless(auth()->user()->can('verify-permintaan'), 403);

        $permintaan = PermintaanObat::findOrFail($id);
        app(VerifyPermintaan::class)->handle(
            $permintaan,
            $this->decisions,
            auth()->id(),
            $this->catatan_verifikasi ?: null,
        );

        $this->select($id);
        session()->flash('status', 'Permintaan diverifikasi.');
    }

    public function render()
    {
        $permintaans = PermintaanObat::with(['facility', 'items'])->orderByDesc('id')->paginate(10);
        $selected = $this->selectedId ? PermintaanObat::with(['items.obat', 'facility', 'verifier'])->find($this->selectedId) : null;

        return view('livewire.perencanaan.permintaan-index', [
            'permintaans' => $permintaans,
            'selected' => $selected,
            'obat' => Obat::orderBy('nama')->get(),
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
        ])->layout('layouts.app', ['title' => 'Permintaan Obat']);
    }

    private function emptyItem(): array
    {
        return ['obat_id' => '', 'jumlah_diminta' => 1, 'keterangan' => ''];
    }
}
