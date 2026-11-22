<?php

namespace App\Actions\Laporan;

use App\Enums\StatusLplpo;
use App\Models\LplpoItem;
use App\Models\PrediksiObat;

class PrediksiRealisasiReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'obat' => 'Obat',
            'periode' => 'Periode',
            'metode' => 'Metode',
            'prediksi' => 'Prediksi',
            'aktual' => 'Aktual',
            'selisih' => 'Selisih',
            'mape' => 'MAPE (%)',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);

        $rows = PrediksiObat::query()
            ->with(['obat', 'facility'])
            ->where('periode_tahun', $tahun)
            ->orderBy('periode_bulan')
            ->get()
            ->map(function (PrediksiObat $prediksi) {
                $aktual = (int) LplpoItem::query()
                    ->where('obat_id', $prediksi->obat_id)
                    ->whereHas('lplpo', fn ($query) => $query
                        ->where('bulan', $prediksi->periode_bulan)
                        ->where('tahun', $prediksi->periode_tahun)
                        ->where('status', StatusLplpo::Diverifikasi->value)
                        ->when($prediksi->facility_id, fn ($q, $facilityId) => $q->where('facility_id', $facilityId)))
                    ->sum('pemakaian');

                $prediksiNilai = (float) $prediksi->nilai_prediksi;
                $mape = $aktual > 0
                    ? round(abs($prediksiNilai - $aktual) / $aktual * 100, 1)
                    : null;

                return [
                    'obat' => $prediksi->obat?->nama,
                    'periode' => sprintf('%02d/%d', $prediksi->periode_bulan, $prediksi->periode_tahun),
                    'metode' => $prediksi->metode_model->value,
                    'prediksi' => round($prediksiNilai, 2),
                    'aktual' => $aktual,
                    'selisih' => round($prediksiNilai - $aktual, 2),
                    'mape' => $mape,
                ];
            })
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
