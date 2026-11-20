<?php

namespace App\Actions\Prediksi;

use App\Enums\StatusLplpo;
use Illuminate\Support\Facades\DB;

class SiapkanDataPrediksi
{
    /**
     * @return array{series:array<int,float>,periods:array<int,array{tahun:int,bulan:int}>,data_points:int}
     */
    public function handle(int $obatId, ?int $facilityId = null, int $months = 24): array
    {
        $rows = DB::table('lplpo_item')
            ->join('lplpo', 'lplpo.id', '=', 'lplpo_item.lplpo_id')
            ->where('lplpo.status', StatusLplpo::Diverifikasi->value)
            ->where('lplpo_item.obat_id', $obatId)
            ->when($facilityId, fn ($q) => $q->where('lplpo.facility_id', $facilityId))
            ->selectRaw('lplpo.tahun, lplpo.bulan, sum(lplpo_item.pemakaian) as total')
            ->groupBy('lplpo.tahun', 'lplpo.bulan')
            ->orderByDesc('lplpo.tahun')
            ->orderByDesc('lplpo.bulan')
            ->limit($months)
            ->get()
            ->reverse()
            ->values();

        return [
            'series' => $rows->map(fn ($row) => (float) $row->total)->all(),
            'periods' => $rows->map(fn ($row) => ['tahun' => (int) $row->tahun, 'bulan' => (int) $row->bulan])->all(),
            'data_points' => $rows->count(),
        ];
    }
}
