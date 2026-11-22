<?php

namespace App\Actions\Laporan;

use App\Models\BatchObat;

class StokAkhirReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'obat' => 'Obat',
            'total_sisa' => 'Total stok',
            'total_nilai' => 'Nilai stok',
        ];

        $rows = BatchObat::query()
            ->with('obat')
            ->where('jumlah_sisa', '>', 0)
            ->selectRaw('obat_id, sum(jumlah_sisa) as total_sisa, sum(jumlah_sisa * harga_beli) as total_nilai')
            ->groupBy('obat_id')
            ->orderBy('obat_id')
            ->get()
            ->map(fn ($batch) => [
                'obat' => $batch->obat?->nama,
                'total_sisa' => (int) $batch->total_sisa,
                'total_nilai' => (float) $batch->total_nilai,
            ])
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
