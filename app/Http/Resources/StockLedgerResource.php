<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'tipe' => $this->tipe->value,
            'jumlah' => $this->jumlah,
            'jumlah_sebelum' => $this->jumlah_sebelum,
            'jumlah_sesudah' => $this->jumlah_sesudah,
            'tipe_referensi' => $this->tipe_referensi->value,
            'referensi_id' => $this->referensi_id,
            'tanggal_transaksi' => $this->tanggal_transaksi?->toIso8601String(),
            'user_id' => $this->user_id,
            'catatan' => $this->catatan,
        ];
    }
}
