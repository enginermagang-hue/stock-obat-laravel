<?php

namespace App\Actions\Laporan;

use App\Models\StockOpnameItem;

class OpnameReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'no_opname' => 'No. opname',
            'periode' => 'Periode',
            'puskesmas' => 'Unit',
            'obat' => 'Obat',
            'stok_sistem' => 'Stok sistem',
            'stok_fisik' => 'Stok fisik',
            'selisih' => 'Selisih',
            'nilai' => 'Nilai selisih',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);
        $facilityId = $params['facility_id'] ?? null;

        $rows = StockOpnameItem::query()
            ->with(['opname.facility', 'batch.obat'])
            ->whereHas('opname', fn ($query) => $query
                ->where('periode_tahun', $tahun)
                ->when($facilityId, fn ($q, $id) => $q->where('facility_id', $id)))
            ->get()
            ->map(fn (StockOpnameItem $item) => [
                'no_opname' => $item->opname?->no_opname,
                'periode' => sprintf('%02d/%d', $item->opname?->periode_bulan ?? 0, $item->opname?->periode_tahun ?? 0),
                'puskesmas' => $item->opname?->facility?->nama ?? 'Gudang RS',
                'obat' => $item->batch?->obat?->nama,
                'stok_sistem' => $item->stok_sistem,
                'stok_fisik' => $item->stok_fisik,
                'selisih' => $item->selisih,
                'nilai' => (float) $item->selisih * (float) $item->harga_beli,
            ])
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
