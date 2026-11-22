<?php

namespace App\Actions\Laporan;

use App\Models\GoodsReceiptItem;

class RealisasiObatReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'obat' => 'Obat',
            'sumber_dana' => 'Sumber dana',
            'jumlah' => 'Jumlah',
            'nilai' => 'Nilai',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);

        $rows = GoodsReceiptItem::query()
            ->with(['obat', 'sumberDana', 'receipt'])
            ->whereHas('receipt', fn ($query) => $query->whereYear('tanggal_terima', $tahun))
            ->get()
            ->groupBy(fn (GoodsReceiptItem $item) => $item->obat_id.'|'.$item->sumber_dana_id)
            ->map(fn ($group) => [
                'obat' => $group->first()->obat?->nama,
                'sumber_dana' => $group->first()->sumberDana?->nama ?? '—',
                'jumlah' => (int) $group->sum('jumlah'),
                'nilai' => (float) $group->sum('subtotal'),
            ])
            ->values()
            ->sortBy([['obat', 'asc'], ['sumber_dana', 'asc']])
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
