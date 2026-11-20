<?php

namespace App\Livewire\Master;

use App\Enums\BentukSediaan;
use App\Enums\GolonganObat;
use App\Models\KategoriObat;
use App\Models\Obat;

class ObatIndex extends MasterIndex
{
    protected function modelClass(): string
    {
        return Obat::class;
    }

    protected function title(): string
    {
        return 'Master Obat';
    }

    protected function columns(): array
    {
        return [
            'kode_obat' => 'Kode',
            'nama' => 'Nama',
            'bentuk_sediaan' => 'Bentuk',
            'golongan' => 'Golongan',
            'stok_minimum' => 'Stok min',
            'aktif' => 'Aktif',
        ];
    }

    protected function fields(): array
    {
        return [
            'kode_obat' => ['label' => 'Kode obat', 'rules' => 'required|string|max:50'],
            'nama' => ['label' => 'Nama', 'rules' => 'required|string|max:255'],
            'kategori_id' => [
                'label' => 'Kategori', 'type' => 'select', 'nullable' => true,
                'options' => KategoriObat::query()->orderBy('nama')->pluck('nama', 'id')->all(),
            ],
            'bentuk_sediaan' => [
                'label' => 'Bentuk sediaan', 'type' => 'select', 'rules' => 'required',
                'options' => collect(BentukSediaan::cases())->mapWithKeys(
                    fn ($case) => [$case->value => ucfirst($case->value)]
                )->all(),
            ],
            'kekuatan' => ['label' => 'Kekuatan', 'nullable' => true],
            'satuan' => ['label' => 'Satuan', 'rules' => 'required|string|max:50'],
            'golongan' => [
                'label' => 'Golongan', 'type' => 'select', 'rules' => 'required',
                'options' => collect(GolonganObat::cases())->mapWithKeys(
                    fn ($case) => [$case->value => str_replace('_', ' ', $case->value)]
                )->all(),
            ],
            'no_izin_edar' => ['label' => 'No. izin edar', 'nullable' => true],
            'produsen' => ['label' => 'Produsen', 'nullable' => true],
            'harga_satuan_terakhir' => ['label' => 'Harga satuan', 'type' => 'number', 'default' => 0, 'rules' => 'numeric|min:0'],
            'stok_minimum' => ['label' => 'Stok minimum', 'type' => 'number', 'default' => 0, 'rules' => 'integer|min:0'],
            'butuh_cold_chain' => ['label' => 'Butuh cold chain', 'type' => 'checkbox'],
            'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
        ];
    }
}
