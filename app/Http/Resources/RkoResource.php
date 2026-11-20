<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RkoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_rko' => $this->no_rko,
            'tahun' => $this->tahun,
            'periode' => $this->periode,
            'tingkat' => $this->tingkat->value,
            'metode' => $this->metode->value,
            'lead_time_bulan' => $this->lead_time_bulan,
            'faktor_pengaman' => (float) $this->faktor_pengaman,
            'sumber_dana_id' => $this->sumber_dana_id,
            'supplier_id' => $this->supplier_id,
            'status' => $this->status->value,
            'versi' => $this->versi,
            'total' => (float) $this->total,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'obat_id' => $item->obat_id,
                'pemakaian_rata_rata' => (float) $item->pemakaian_rata_rata,
                'estimasi_pemakaian' => (float) $item->estimasi_pemakaian,
                'stok_akhir' => $item->stok_akhir,
                'stok_dalam_pesanan' => $item->stok_dalam_pesanan,
                'stok_pengaman' => (float) $item->stok_pengaman,
                'kebutuhan_bersih' => (float) $item->kebutuhan_bersih,
                'harga_estimasi' => (float) $item->harga_estimasi,
                'total_estimasi' => (float) $item->total_estimasi,
            ])),
        ];
    }
}
