<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_po' => $this->no_po,
            'supplier_id' => $this->supplier_id,
            'sumber_dana_id' => $this->sumber_dana_id,
            'tanggal_po' => $this->tanggal_po?->toDateString(),
            'status' => $this->status->value,
            'total' => (float) $this->total,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'obat_id' => $item->obat_id,
                'jumlah' => $item->jumlah,
                'harga_beli' => (float) $item->harga_beli,
                'jumlah_diterima' => $item->jumlah_diterima,
                'subtotal' => (float) $item->subtotal,
            ])),
        ];
    }
}
