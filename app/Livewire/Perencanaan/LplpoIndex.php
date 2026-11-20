<?php

namespace App\Livewire\Perencanaan;

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Actions\Lplpo\VerifyLplpo;
use App\Actions\Permintaan\CreatePermintaanFromLplpo;
use App\Enums\StatusLplpo;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use Livewire\Component;
use Livewire\WithPagination;

class LplpoIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $selectedId = null;
    public ?int $facility_id = null;
    public int $bulan;
    public int $tahun;
    public string $catatan = '';
    public array $items = [];

    public function mount(): void
    {
        $this->bulan = (int) now()->month;
        $this->tahun = (int) now()->year;
        $this->facility_id = auth()->user()->facility_id;
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
        abort_unless(auth()->user()->can('create-lplpo'), 403);

        $data = $this->validate([
            'facility_id' => 'required|exists:facilities,id',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.pemasukan' => 'nullable|integer|min:0',
            'items.*.pemakaian' => 'nullable|integer|min:0',
            'items.*.stok_akhir' => 'nullable|integer|min:0',
            'items.*.jumlah_diminta' => 'nullable|integer|min:0',
        ]);

        $facilityId = auth()->user()->facility_id ?: (int) $data['facility_id'];

        $lplpo = app(GenerateLplpo::class)->handle(
            $facilityId,
            $data['bulan'],
            $data['tahun'],
            $data['items'],
            auth()->id(),
            $this->catatan ?: null,
        );

        $this->selectedId = $lplpo->id;
        $this->reset(['showForm', 'catatan']);
        $this->items = [$this->emptyItem()];
        session()->flash('status', 'LPLPO disimpan sebagai draft.');
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
    }

    public function submit(int $id): void
    {
        abort_unless(auth()->user()->can('create-lplpo'), 403);
        app(SubmitLplpo::class)->handle(Lplpo::findOrFail($id));
        session()->flash('status', 'LPLPO dikirim untuk verifikasi.');
    }

    public function verify(int $id, bool $approve): void
    {
        abort_unless(auth()->user()->can('verify-lplpo'), 403);
        app(VerifyLplpo::class)->handle(Lplpo::findOrFail($id), $approve, auth()->id());
        session()->flash('status', $approve ? 'LPLPO diverifikasi.' : 'LPLPO ditolak.');
    }

    public function createPermintaan(int $id): void
    {
        abort_unless(auth()->user()->can('create-permintaan'), 403);
        app(CreatePermintaanFromLplpo::class)->handle(Lplpo::findOrFail($id), auth()->id());
        session()->flash('status', 'Permintaan dibuat dari LPLPO.');
        $this->redirectRoute('permintaan.index', navigate: true);
    }

    public function render()
    {
        $lplpos = Lplpo::with(['facility', 'items'])->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('versi')->paginate(10);
        $selected = $this->selectedId ? Lplpo::with(['items.obat', 'facility', 'verifier'])->find($this->selectedId) : null;

        return view('livewire.perencanaan.lplpo-index', [
            'lplpos' => $lplpos,
            'selected' => $selected,
            'obat' => Obat::orderBy('nama')->get(),
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
        ])->layout('layouts.app', ['title' => 'LPLPO']);
    }

    private function emptyItem(): array
    {
        return ['obat_id' => '', 'pemasukan' => 0, 'pemakaian' => 0, 'stok_akhir' => 0, 'jumlah_diminta' => 0, 'keterangan' => ''];
    }
}
