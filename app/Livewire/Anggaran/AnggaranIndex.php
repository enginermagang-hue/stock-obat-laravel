<?php

namespace App\Livewire\Anggaran;

use App\Actions\Anggaran\BuildLaporanAnggaranRealisasi;
use App\Actions\Anggaran\SimpanAnggaran;
use App\Models\Anggaran;
use App\Models\Facility;
use App\Models\SumberDana;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AnggaranIndex extends Component
{
    public int $tahun;

    public string $facilityFilter = '0';

    public ?int $sumber_dana_id = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $form_sumber_dana_id = null;

    public int $form_facility_id = Anggaran::RS_FACILITY_ID;

    public float $jumlah_anggaran = 0;

    public string $keterangan = '';

    public function mount(): void
    {
        $this->tahun = (int) now()->year;
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form_sumber_dana_id = $this->sumber_dana_id;
        $this->form_facility_id = $this->facilityFilter === 'all' ? Anggaran::RS_FACILITY_ID : (int) $this->facilityFilter;
        $this->jumlah_anggaran = 0;
        $this->keterangan = '';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('manage-anggaran'), 403);

        $anggaran = Anggaran::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $anggaran->id;
        $this->form_sumber_dana_id = $anggaran->sumber_dana_id;
        $this->form_facility_id = $anggaran->facility_id;
        $this->jumlah_anggaran = (float) $anggaran->jumlah_anggaran;
        $this->keterangan = (string) $anggaran->keterangan;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('manage-anggaran'), 403);

        $data = $this->validate([
            'form_sumber_dana_id' => 'required|exists:sumber_dana,id',
            'form_facility_id' => [
                'required',
                'integer',
                'min:0',
                $this->form_facility_id > 0
                    ? Rule::exists('facilities', 'id')
                    : Rule::in([Anggaran::RS_FACILITY_ID]),
            ],
            'jumlah_anggaran' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:1000',
        ], [], [
            'form_sumber_dana_id' => 'sumber dana',
            'form_facility_id' => 'unit',
            'jumlah_anggaran' => 'jumlah anggaran',
        ]);

        app(SimpanAnggaran::class)->handle(
            $this->tahun,
            $data['form_sumber_dana_id'],
            $data['form_facility_id'],
            (float) $data['jumlah_anggaran'],
            $this->keterangan ?: null,
            allowUpdate: $this->editingId !== null,
        );

        $this->reset(['showForm', 'editingId', 'keterangan']);
        $this->jumlah_anggaran = 0;
        session()->flash('status', 'Pagu anggaran disimpan.');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('manage-anggaran'), 403);

        Anggaran::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->reset(['showForm', 'editingId', 'keterangan']);
            $this->jumlah_anggaran = 0;
        }

        session()->flash('status', 'Pagu anggaran dihapus.');
    }

    public function render()
    {
        $facilityId = $this->facilityFilter === 'all' ? null : (int) $this->facilityFilter;

        $report = app(BuildLaporanAnggaranRealisasi::class)->handle(
            $this->tahun,
            $this->sumber_dana_id,
            $facilityId,
        );

        return view('livewire.anggaran.anggaran-index', [
            'report' => $report,
            'sumberDana' => SumberDana::orderBy('nama')->get(),
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
        ])->layout('layouts.app', ['title' => 'Anggaran & Realisasi']);
    }
}
