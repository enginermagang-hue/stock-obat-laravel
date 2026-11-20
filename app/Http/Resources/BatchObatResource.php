<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchObatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_batch' => $this->no_batch,
            'obat' => [
                'id' => $this->obat_id,
                'kode' => $this->obat?->kode_obat,
                'nama' => $this->obat?->nama,
            ],
            'tanggal_kedaluwarsa' => $this->tanggal_kedaluwarsa?->toDateString(),
            'jumlah_awal' => $this->jumlah_awal,
            'jumlah_sisa' => $this->jumlah_sisa,
            'harga_beli' => (float) $this->harga_beli,
            'lokasi_rak' => $this->lokasi_rak,
            'sumber_dana_id' => $this->sumber_dana_id,
            'supplier_id' => $this->supplier_id,
            'aktif' => $this->aktif,
        ];
    }
}
