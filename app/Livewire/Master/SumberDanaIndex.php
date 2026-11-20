<?php

namespace App\Livewire\Master;

use App\Enums\JenisSumberDana;
use App\Models\SumberDana;

class SumberDanaIndex extends MasterIndex
{
    protected function modelClass(): string
    {
        return SumberDana::class;
    }

    protected function title(): string
    {
        return 'Master Sumber Dana';
    }

    protected function columns(): array
    {
        return [
            'kode' => 'Kode',
            'nama' => 'Nama',
            'jenis' => 'Jenis',
            'kode_rekening' => 'Kode rekening',
            'aktif' => 'Aktif',
        ];
    }

    protected function fields(): array
    {
        return [
            'kode' => ['label' => 'Kode', 'rules' => 'required|string|max:50'],
            'nama' => ['label' => 'Nama', 'rules' => 'required|string|max:255'],
            'jenis' => [
                'label' => 'Jenis', 'type' => 'select', 'rules' => 'required',
                'options' => collect(JenisSumberDana::cases())->mapWithKeys(
                    fn ($case) => [$case->value => str_replace('_', ' ', $case->value)]
                )->all(),
            ],
            'kode_rekening' => ['label' => 'Kode rekening', 'nullable' => true],
            'program' => ['label' => 'Program', 'nullable' => true],
            'keterangan' => ['label' => 'Keterangan', 'nullable' => true],
            'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
        ];
    }
}
