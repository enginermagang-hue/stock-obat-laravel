<?php

namespace App\Actions\Anggaran;

use Illuminate\Support\Collection;

class BuildLaporanAnggaranRealisasi
{
    public function __construct(private readonly RekapRealisasi $rekap) {}

    /** @return array{rows:Collection,pagu:float,komitmen:float,realisasi:float,sisa:float} */
    public function handle(int $tahun, ?int $sumberDanaId = null, ?int $facilityId = null): array
    {
        $rows = $this->rekap->handle($tahun, $sumberDanaId, $facilityId);

        return [
            'rows' => $rows,
            'pagu' => (float) $rows->sum('pagu'),
            'komitmen' => (float) $rows->sum('komitmen_bersih'),
            'realisasi' => (float) $rows->sum('realisasi'),
            'sisa' => (float) $rows->sum('sisa'),
        ];
    }
}
