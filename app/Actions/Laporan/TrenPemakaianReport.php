<?php

namespace App\Actions\Laporan;

use App\Enums\StatusLplpo;
use App\Models\LplpoItem;

class TrenPemakaianReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'periode' => 'Periode',
            'obat' => 'Obat',
            'pemakaian' => 'Pemakaian',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);
        $obatId = $params['obat_id'] ?? null;

        $rows = LplpoItem::query()
            ->with(['lplpo', 'obat'])
            ->whereHas('lplpo', fn ($query) => $query
                ->where('tahun', $tahun)
                ->where('status', StatusLplpo::Diverifikasi->value))
            ->when($obatId, fn ($query, $id) => $query->where('obat_id', $id))
            ->get()
            ->groupBy(fn (LplpoItem $item) => ($item->lplpo?->bulan ?? 0).'|'.$item->obat_id)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'periode' => sprintf('%02d/%d', $first->lplpo?->bulan ?? 0, $first->lplpo?->tahun ?? 0),
                    'obat' => $first->obat?->nama,
                    'pemakaian' => (int) $group->sum('pemakaian'),
                ];
            })
            ->values()
            ->sortBy([['periode', 'asc'], ['obat', 'asc']])
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
