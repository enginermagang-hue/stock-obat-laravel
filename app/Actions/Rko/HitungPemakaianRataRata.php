<?php

namespace App\Actions\Rko;

use App\Enums\StatusLplpo;
use App\Models\Obat;
use Illuminate\Support\Facades\DB;

class HitungPemakaianRataRata
{
    public function handle(int $obatId, ?int $facilityId = null, int $limit = 6): float
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
            ->limit($limit)
            ->pluck('total');

        if ($rows->isEmpty()) {
            return (float) (Obat::find($obatId)?->stok_minimum ?? 0);
        }

        return round((float) $rows->avg(), 2);
    }
}
