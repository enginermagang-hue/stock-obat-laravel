<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_opname' => $this->no_opname,
            'tanggal' => $this->tanggal?->toDateString(),
            'periode_bulan' => $this->periode_bulan,
            'periode_tahun' => $this->periode_tahun,
            'tipe' => $this->tipe->value,
            'mode' => $this->mode->value,
            'status' => $this->status->value,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'batch_id' => $item->batch_id,
                'stok_sistem' => $item->stok_sistem,
                'stok_fisik' => $item->stok_fisik,
                'selisih' => $item->selisih,
            ])),
        ];
    }
}
