<?php

namespace App\Actions\Anggaran;

use App\Models\Anggaran;
use Illuminate\Support\Collection;

class RekapRealisasi
{
    public function __construct(private readonly ValidatePagu $validate) {}

    /** @return Collection<int, array{anggaran:Anggaran,pagu:float,komitmen_bersih:float,realisasi:float,sisa:float,persen_realisasi:float,persen_komitmen:float}> */
    public function handle(int $tahun, ?int $sumberDanaId = null, ?int $facilityId = null): Collection
    {
        return Anggaran::query()
            ->with(['sumberDana', 'facility'])
            ->where('tahun', $tahun)
            ->when($sumberDanaId, fn ($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->when($facilityId !== null, fn ($q) => $q->where('facility_id', $facilityId))
            ->orderBy('sumber_dana_id')
            ->orderBy('facility_id')
            ->get()
            ->map(function (Anggaran $anggaran) use ($tahun) {
                $metrics = $this->validate->handle(
                    $tahun,
                    $anggaran->sumber_dana_id,
                    $anggaran->facility_id,
                );

                $pagu = max(0.01, $metrics['pagu']);

                return [
                    'anggaran' => $anggaran,
                    'pagu' => $metrics['pagu'],
                    'komitmen_bersih' => $metrics['komitmen_bersih'],
                    'realisasi' => $metrics['realisasi'],
                    'sisa' => $metrics['sisa'],
                    'persen_realisasi' => round(($metrics['realisasi'] / $pagu) * 100, 1),
                    'persen_komitmen' => round(($metrics['komitmen_bersih'] / $pagu) * 100, 1),
                ];
            });
    }
}
