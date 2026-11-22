<?php

namespace App\Actions\Laporan;

use App\Models\LplpoItem;

class LplpoRecapReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'periode' => 'Periode',
            'puskesmas' => 'Puskesmas',
            'obat' => 'Obat',
            'pemakaian' => 'Pemakaian',
            'stok_akhir' => 'Stok akhir',
            'stok_sistem' => 'Stok sistem',
            'selisih' => 'Selisih',
            'diminta' => 'Diminta',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);
        $facilityId = $params['facility_id'] ?? null;

        $rows = LplpoItem::query()
            ->with(['lplpo.facility', 'obat'])
            ->whereHas('lplpo', fn ($query) => $query
                ->where('tahun', $tahun)
                ->when($facilityId, fn ($q, $id) => $q->where('facility_id', $id)))
            ->get()
            ->map(fn (LplpoItem $item) => [
                'periode' => sprintf('%02d/%d', $item->lplpo?->bulan ?? 0, $item->lplpo?->tahun ?? 0),
                'puskesmas' => $item->lplpo?->facility?->nama,
                'obat' => $item->obat?->nama,
                'pemakaian' => $item->pemakaian,
                'stok_akhir' => $item->stok_akhir,
                'stok_sistem' => $item->stok_akhir_sistem,
                'selisih' => $item->stok_akhir - $item->stok_akhir_sistem,
                'diminta' => $item->jumlah_diminta,
            ])
            ->sortBy([['periode', 'asc'], ['puskesmas', 'asc'], ['obat', 'asc']])
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
