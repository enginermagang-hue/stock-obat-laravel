<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LplpoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_lplpo' => $this->no_lplpo,
            'facility_id' => $this->facility_id,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'versi' => $this->versi,
            'status' => $this->status->value,
            'tanggal_penyerahan' => $this->tanggal_penyerahan?->toDateString(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'obat_id' => $item->obat_id,
                'stok_awal' => $item->stok_awal,
                'pemasukan' => $item->pemasukan,
                'pemakaian' => $item->pemakaian,
                'stok_akhir' => $item->stok_akhir,
                'stok_akhir_sistem' => $item->stok_akhir_sistem,
                'stok_minimum' => $item->stok_minimum,
                'jumlah_diminta' => $item->jumlah_diminta,
            ])),
        ];
    }
}
