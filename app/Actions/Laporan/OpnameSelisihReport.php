<?php

namespace App\Actions\Laporan;

use App\Models\StockOpnameItem;

class OpnameSelisihReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'obat' => 'Obat',
            'periode' => 'Periode',
            'total_selisih' => 'Total selisih',
            'nilai' => 'Nilai selisih',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);
        $facilityId = $params['facility_id'] ?? null;

        $rows = StockOpnameItem::query()
            ->with(['opname', 'batch.obat'])
            ->whereHas('opname', fn ($query) => $query
                ->where('periode_tahun', $tahun)
                ->when($facilityId, fn ($q, $id) => $q->where('facility_id', $id)))
            ->get()
            ->groupBy(fn (StockOpnameItem $item) => ($item->batch?->obat_id ?? 0).'|'.($item->opname?->periode_bulan ?? 0))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'obat' => $first->batch?->obat?->nama,
                    'periode' => sprintf('%02d/%d', $first->opname?->periode_bulan ?? 0, $first->opname?->periode_tahun ?? 0),
                    'total_selisih' => (int) $group->sum('selisih'),
                    'nilai' => (float) $group->sum(fn (StockOpnameItem $item) => $item->selisih * (float) $item->harga_beli),
                ];
            })
            ->values()
            ->sortBy([['obat', 'asc'], ['periode', 'asc']])
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
