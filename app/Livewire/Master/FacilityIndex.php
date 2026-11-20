<?php

namespace App\Livewire\Master;

use App\Models\Facility;

class FacilityIndex extends MasterIndex
{
    protected function modelClass(): string
    {
        return Facility::class;
    }

    protected function title(): string
    {
        return 'Puskesmas / Fasilitas';
    }

    protected function columns(): array
    {
        return [
            'kode' => 'Kode',
            'nama' => 'Nama',
            'jenis' => 'Jenis',
            'kontak' => 'Kontak',
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
                'options' => ['rs' => 'RS', 'puskesmas' => 'Puskesmas'],
            ],
            'alamat' => ['label' => 'Alamat', 'nullable' => true],
            'kontak' => ['label' => 'Kontak', 'nullable' => true],
            'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
        ];
    }
}
