<?php

namespace App\Actions\Prediksi;

use App\Enums\StatusPrediksi;
use App\Models\PrediksiObat;
use Illuminate\Support\Facades\DB;

class EvaluasiAkurasi
{
    public function handle(PrediksiObat $prediksi): PrediksiObat
    {
        $prediksi->update([
            'status' => StatusPrediksi::Divalidasi,
            'evaluated_at' => now(),
        ]);

        return $prediksi->refresh();
    }

    /** @return array<int, array{metode:string,total:int,avg_mape:float|null}> */
    public function summary(?int $tahun = null): array
    {
        return DB::table('prediksi_obat')
            ->when($tahun, fn ($q) => $q->where('periode_tahun', $tahun))
            ->selectRaw('metode_model as metode, count(*) as total, avg(mape) as avg_mape')
            ->groupBy('metode_model')
            ->get()
            ->map(fn ($row) => [
                'metode' => $row->metode,
                'total' => (int) $row->total,
                'avg_mape' => $row->avg_mape !== null ? round((float) $row->avg_mape, 2) : null,
            ])
            ->all();
    }
}
