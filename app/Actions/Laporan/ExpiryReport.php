<?php

namespace App\Actions\Laporan;

use App\Models\BatchObat;

class ExpiryReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'obat' => 'Obat',
            'batch' => 'Batch',
            'kedaluwarsa' => 'Kedaluwarsa',
            'sisa' => 'Sisa',
            'nilai' => 'Nilai',
            'status' => 'Status',
        ];

        $days = (int) ($params['days'] ?? 90);

        $rows = BatchObat::query()
            ->with('obat')
            ->where('jumlah_sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '<=', today()->addDays($days))
            ->orderBy('tanggal_kedaluwarsa')
            ->get()
            ->map(fn (BatchObat $batch) => [
                'obat' => $batch->obat?->nama,
                'batch' => $batch->no_batch,
                'kedaluwarsa' => $batch->tanggal_kedaluwarsa?->format('d-m-Y'),
                'sisa' => $batch->jumlah_sisa,
                'nilai' => (float) $batch->jumlah_sisa * (float) $batch->harga_beli,
                'status' => $batch->tanggal_kedaluwarsa?->isPast() ? 'kedaluwarsa' : 'segera',
            ])
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
