<?php

namespace App\Livewire\Master;

use App\Models\Supplier;

class SupplierIndex extends MasterIndex
{
    protected function modelClass(): string
    {
        return Supplier::class;
    }

    protected function title(): string
    {
        return 'Master Supplier';
    }

    protected function columns(): array
    {
        return [
            'kode' => 'Kode',
            'nama' => 'Nama',
            'kontak' => 'Kontak',
            'npwp' => 'NPWP',
            'aktif' => 'Aktif',
        ];
    }

    protected function fields(): array
    {
        return [
            'kode' => ['label' => 'Kode', 'rules' => 'required|string|max:50'],
            'nama' => ['label' => 'Nama', 'rules' => 'required|string|max:255'],
            'alamat' => ['label' => 'Alamat', 'nullable' => true],
            'kontak' => ['label' => 'Kontak', 'nullable' => true],
            'npwp' => ['label' => 'NPWP', 'nullable' => true],
            'no_izin_usaha' => ['label' => 'No. izin usaha', 'nullable' => true],
            'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
        ];
    }
}
