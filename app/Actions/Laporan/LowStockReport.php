<?php

namespace App\Actions\Laporan;

use App\Models\Obat;

class LowStockReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'obat' => 'Obat',
            'stok' => 'Stok',
            'stok_minimum' => 'Stok minimum',
            'defisit' => 'Defisit',
        ];

        $rows = Obat::query()
            ->withSum('batches as total_sisa', 'jumlah_sisa')
            ->whereRaw('(select coalesce(sum(jumlah_sisa), 0) from batch_obat where batch_obat.obat_id = obat.id) <= obat.stok_minimum')
            ->orderBy('nama')
            ->get()
            ->map(fn (Obat $obat) => [
                'obat' => $obat->nama,
                'stok' => (int) ($obat->total_sisa ?? 0),
                'stok_minimum' => (int) $obat->stok_minimum,
                'defisit' => max(0, (int) $obat->stok_minimum - (int) ($obat->total_sisa ?? 0)),
            ])
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
