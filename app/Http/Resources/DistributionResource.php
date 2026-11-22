<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_surat' => $this->no_surat,
            'no_sbbk' => $this->no_sbbk,
            'facility_id' => $this->facility_id,
            'facility' => $this->whenLoaded('facility', fn () => [
                'id' => $this->facility->id,
                'kode' => $this->facility->kode,
                'nama' => $this->facility->nama,
            ]),
            'permintaan_id' => $this->permintaan_id,
            'permintaan' => $this->whenLoaded('permintaan', fn () => $this->permintaan ? [
                'id' => $this->permintaan->id,
                'no_permintaan' => $this->permintaan->no_permintaan,
            ] : null),
            'tanggal' => $this->tanggal?->toDateString(),
            'tanggal_keluar' => $this->tanggal_keluar?->toDateString(),
            'status' => $this->status->value,
            'pengirim_id' => $this->pengirim_id,
            'penerima_nama' => $this->penerima_nama,
            'penerima_nip' => $this->penerima_nip,
            'penerima_jabatan' => $this->penerima_jabatan,
            'penerima_unit' => $this->penerima_unit,
            'no_bast' => $this->no_bast,
            'tanggal_diterima' => $this->tanggal_diterima?->toDateString(),
            'file_tanda_terima' => $this->file_tanda_terima,
            'catatan' => $this->catatan,
            'total' => $this->whenLoaded('items', fn () => (float) $this->items->sum('subtotal')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'permintaan_item_id' => $item->permintaan_item_id,
                'obat_id' => $item->obat_id,
                'obat' => $item->relationLoaded('obat') ? [
                    'id' => $item->obat->id,
                    'nama' => $item->obat->nama,
                    'kode_obat' => $item->obat->kode_obat,
                ] : null,
                'batch_id' => $item->batch_id,
                'no_batch' => $item->relationLoaded('batch') ? $item->batch->no_batch : null,
                'tanggal_kedaluwarsa' => $item->relationLoaded('batch')
                    ? $item->batch->tanggal_kedaluwarsa?->toDateString()
                    : null,
                'jumlah' => $item->jumlah,
                'harga_satuan' => (float) $item->harga_satuan,
                'subtotal' => (float) $item->subtotal,
                'keterangan' => $item->keterangan,
            ])),
        ];
    }
}
