<?php

namespace App\Actions\Laporan;

use App\Enums\StatusDistribusi;
use App\Models\DistributionItem;
use App\Models\PermintaanObatItem;

class PermintaanRealisasiReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'no_permintaan' => 'No. permintaan',
            'puskesmas' => 'Puskesmas',
            'obat' => 'Obat',
            'diminta' => 'Diminta',
            'disetujui' => 'Disetujui',
            'didistribusikan' => 'Didistribusikan',
            'fulfillment' => 'Fulfillment (%)',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);
        $facilityId = $params['facility_id'] ?? null;

        $rows = PermintaanObatItem::query()
            ->with(['permintaan.facility', 'obat'])
            ->whereHas('permintaan', fn ($query) => $query
                ->whereYear('tanggal', $tahun)
                ->when($facilityId, fn ($q, $id) => $q->where('facility_id', $id)))
            ->get()
            ->map(function (PermintaanObatItem $item) {
                $approved = (int) ($item->jumlah_disetujui ?? $item->jumlah_diminta);

                $distributed = (int) DistributionItem::query()
                    ->where('permintaan_item_id', $item->id)
                    ->whereHas('distribution', fn ($query) => $query
                        ->whereIn('status', [StatusDistribusi::Dikirim->value, StatusDistribusi::Diterima->value]))
                    ->sum('jumlah');

                return [
                    'no_permintaan' => $item->permintaan?->no_permintaan,
                    'puskesmas' => $item->permintaan?->facility?->nama,
                    'obat' => $item->obat?->nama,
                    'diminta' => $item->jumlah_diminta,
                    'disetujui' => $approved,
                    'didistribusikan' => $distributed,
                    'fulfillment' => $approved > 0 ? round($distributed / $approved * 100, 1) : 0,
                ];
            })
            ->values()
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
