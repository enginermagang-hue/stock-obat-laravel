<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_penyesuaian' => $this->no_penyesuaian,
            'tipe' => $this->tipe->value,
            'tanggal' => $this->tanggal?->toDateString(),
            'status' => $this->status->value,
            'stock_opname_id' => $this->stock_opname_id,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'batch_id' => $item->batch_id,
                'obat_id' => $item->obat_id,
                'jumlah' => $item->jumlah,
                'alasan' => $item->alasan,
                'nilai' => (float) $item->nilai,
            ])),
        ];
    }
}
