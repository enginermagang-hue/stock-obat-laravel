<?php

namespace App\Actions\Laporan;

use App\Models\PrediksiObat;

class PrediksiAkurasiReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'metode' => 'Metode',
            'jumlah' => 'Jumlah prediksi',
            'rata_rata_mape' => 'Rata-rata MAPE (%)',
            'mape_terbaik' => 'MAPE terbaik (%)',
            'jumlah_valid' => 'Prediksi tervalidasi',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);

        $rows = PrediksiObat::query()
            ->where('periode_tahun', $tahun)
            ->get()
            ->groupBy(fn (PrediksiObat $prediksi) => $prediksi->metode_model->value)
            ->map(function ($group, string $metode) {
                $mapes = $group
                    ->pluck('mape')
                    ->filter(fn ($mape) => $mape !== null)
                    ->map(fn ($mape) => (float) $mape);

                return [
                    'metode' => $metode,
                    'jumlah' => $group->count(),
                    'rata_rata_mape' => $mapes->isNotEmpty() ? round($mapes->avg(), 1) : null,
                    'mape_terbaik' => $mapes->isNotEmpty() ? round($mapes->min(), 1) : null,
                    'jumlah_valid' => $mapes->count(),
                ];
            })
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
