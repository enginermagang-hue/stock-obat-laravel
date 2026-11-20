<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnggaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sumber_dana_id' => $this->sumber_dana_id,
            'sumber_dana' => $this->whenLoaded('sumberDana', fn () => $this->sumberDana?->nama),
            'tahun' => $this->tahun,
            'facility_id' => $this->facility_id,
            'unit' => $this->unitLabel(),
            'jumlah_anggaran' => (float) $this->jumlah_anggaran,
            'status' => $this->status,
            'versi' => $this->versi,
            'keterangan' => $this->keterangan,
        ];
    }
}
