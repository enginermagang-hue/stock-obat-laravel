<?php

namespace App\Actions\Prediksi;

use App\Actions\Rko\HitungPemakaianRataRata;
use App\Enums\MetodePrediksi;
use App\Enums\StatusPrediksi;
use App\Models\Obat;
use App\Models\PrediksiObat;
use App\Scopes\FacilityScope;
use App\Services\Prediksi\HoltWinters;
use Illuminate\Support\Collection;

class PrediksiKebutuhanObat
{
    public function __construct(
        private readonly HoltWinters $holtWinters,
        private readonly SiapkanDataPrediksi $data,
        private readonly HitungPemakaianRataRata $average,
    ) {}

    /** @return Collection<int, PrediksiObat> */
    public function handle(
        int $periodeBulan,
        int $periodeTahun,
        ?int $facilityId = null,
        ?int $userId = null,
        ?array $obatIds = null,
    ): Collection {
        $obatQuery = Obat::query()->where('aktif', true)->orderBy('nama');

        if ($obatIds) {
            $obatQuery->whereIn('id', $obatIds);
        }

        $results = collect();

        foreach ($obatQuery->get() as $obat) {
            $dataset = $this->data->handle($obat->id, $facilityId);
            $lastPeriod = $dataset['periods'] ? end($dataset['periods']) : ['tahun' => $periodeTahun, 'bulan' => $periodeBulan];
            $horizon = max(1, (($periodeTahun - $lastPeriod['tahun']) * 12) + ($periodeBulan - $lastPeriod['bulan']));

            if ($dataset['data_points'] < 12) {
                $rataRata = $this->average->handle($obat->id, $facilityId);
                $prediction = PrediksiObat::withoutGlobalScope(FacilityScope::class)->updateOrCreate(
                    [
                        'obat_id' => $obat->id,
                        'facility_id' => $facilityId,
                        'periode_bulan' => $periodeBulan,
                        'periode_tahun' => $periodeTahun,
                        'metode_model' => MetodePrediksi::Historis->value,
                        'versi_model' => 'historis-1.0',
                    ],
                    [
                        'nilai_prediksi' => $rataRata * $horizon,
                        'interval_bawah' => $rataRata * 0.8 * $horizon,
                        'interval_atas' => $rataRata * 1.2 * $horizon,
                        'confidence_score' => 0.5,
                        'tanggal_prediksi' => now()->toDateString(),
                        'data_snapshot' => $dataset,
                        'faktor_penjelas' => [
                            'mode' => 'fallback',
                            'alasan' => 'Data historis kurang dari 12 bulan',
                        ],
                        'status' => StatusPrediksi::Draft,
                        'mape' => null,
                    ],
                );

                $results->push($prediction);

                continue;
            }

            $result = $this->holtWinters->forecast($dataset['series'], $horizon);
            $confidence = max(0, min(1, 1 - ($result['mape'] / 100)));

            $prediction = PrediksiObat::withoutGlobalScope(FacilityScope::class)->updateOrCreate(
                [
                    'obat_id' => $obat->id,
                    'facility_id' => $facilityId,
                    'periode_bulan' => $periodeBulan,
                    'periode_tahun' => $periodeTahun,
                    'metode_model' => MetodePrediksi::HoltWinters->value,
                    'versi_model' => 'holt-winters-1.0',
                ],
                [
                    'nilai_prediksi' => $result['forecast'],
                    'interval_bawah' => $result['lower'],
                    'interval_atas' => $result['upper'],
                    'confidence_score' => $confidence,
                    'tanggal_prediksi' => now()->toDateString(),
                    'data_snapshot' => $dataset + [
                        'alpha' => $result['alpha'],
                        'beta' => $result['beta'],
                        'gamma' => $result['gamma'],
                        'seasonal' => $result['seasonal'],
                        'horizon' => $horizon,
                    ],
                    'faktor_penjelas' => [
                        'metode' => 'holt_winters',
                        'tren' => $result['trend'] > 0 ? 'naik' : ($result['trend'] < 0 ? 'turun' : 'datar'),
                        'musiman' => $result['seasonal'],
                        'mape' => $result['mape'],
                        'catatan' => 'Rekomendasi saja; apoteker tetap menyetujui RKO.',
                    ],
                    'status' => StatusPrediksi::Draft,
                    'mape' => $result['mape'],
                ],
            );

            $results->push($prediction);
        }

        return $results;
    }
}
