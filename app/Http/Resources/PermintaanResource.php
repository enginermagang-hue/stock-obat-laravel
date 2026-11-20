<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermintaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_permintaan' => $this->no_permintaan,
            'facility_id' => $this->facility_id,
            'lplpo_id' => $this->lplpo_id,
            'sumber' => $this->sumber->value,
            'periode_awal' => $this->periode_awal?->toDateString(),
            'periode_akhir' => $this->periode_akhir?->toDateString(),
            'tanggal' => $this->tanggal?->toDateString(),
            'status' => $this->status->value,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'obat_id' => $item->obat_id,
                'jumlah_diminta' => $item->jumlah_diminta,
                'jumlah_disetujui' => $item->jumlah_disetujui,
                'status_item' => $item->status_item?->value,
                'keterangan' => $item->keterangan,
            ])),
        ];
    }
}
