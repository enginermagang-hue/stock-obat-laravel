<?php

namespace App\Actions\Laporan;

use App\Enums\StatusDistribusi;
use App\Models\DistributionItem;

class DistribusiNilaiReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'sumber_dana' => 'Sumber dana',
            'puskesmas' => 'Puskesmas',
            'jumlah_item' => 'Jumlah item',
            'nilai' => 'Nilai distribusi',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);

        $rows = DistributionItem::query()
            ->with(['batch.sumberDana', 'distribution.facility'])
            ->whereHas('distribution', fn ($query) => $query
                ->whereYear('tanggal', $tahun)
                ->whereIn('status', [StatusDistribusi::Dikirim->value, StatusDistribusi::Diterima->value]))
            ->get()
            ->groupBy(fn (DistributionItem $item) => ($item->batch?->sumber_dana_id ?? 0).'|'.$item->distribution?->facility_id)
            ->map(fn ($group) => [
                'sumber_dana' => $group->first()->batch?->sumberDana?->nama ?? '—',
                'puskesmas' => $group->first()->distribution?->facility?->nama ?? '—',
                'jumlah_item' => (int) $group->sum('jumlah'),
                'nilai' => (float) $group->sum('subtotal'),
            ])
            ->values()
            ->sortBy([['sumber_dana', 'asc'], ['puskesmas', 'asc']])
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
