<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrediksiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'obat_id' => $this->obat_id,
            'facility_id' => $this->facility_id,
            'periode_bulan' => $this->periode_bulan,
            'periode_tahun' => $this->periode_tahun,
            'nilai_prediksi' => (float) $this->nilai_prediksi,
            'interval_bawah' => $this->interval_bawah !== null ? (float) $this->interval_bawah : null,
            'interval_atas' => $this->interval_atas !== null ? (float) $this->interval_atas : null,
            'confidence_score' => $this->confidence_score !== null ? (float) $this->confidence_score : null,
            'metode_model' => $this->metode_model->value,
            'versi_model' => $this->versi_model,
            'status' => $this->status->value,
            'mape' => $this->mape !== null ? (float) $this->mape : null,
            'faktor_penjelas' => $this->faktor_penjelas,
            'tanggal_prediksi' => $this->tanggal_prediksi?->toDateString(),
        ];
    }
}
