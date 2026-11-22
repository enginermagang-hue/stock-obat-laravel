<?php

namespace App\Actions\Laporan;

use App\Enums\StatusRko;
use App\Models\GoodsReceiptItem;
use App\Models\Rko;

class RkoRealisasiReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'no_rko' => 'No. RKO',
            'periode' => 'Periode',
            'sumber_dana' => 'Sumber dana',
            'total_rko' => 'Total RKO',
            'realisasi_po' => 'Realisasi pembelian',
            'selisih' => 'Selisih',
            'persen' => 'Realisasi (%)',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);

        $rows = Rko::query()
            ->with(['sumberDana', 'items'])
            ->where('tahun', $tahun)
            ->where('status', StatusRko::Disetujui->value)
            ->orderBy('periode')
            ->get()
            ->map(function (Rko $rko) use ($tahun) {
                $totalRko = (float) ($rko->total ?: $rko->items->sum('total_estimasi'));

                $realisasi = (float) GoodsReceiptItem::query()
                    ->whereHas('receipt', fn ($query) => $query->whereYear('tanggal_terima', $tahun))
                    ->when($rko->sumber_dana_id, fn ($query, $id) => $query->where('sumber_dana_id', $id))
                    ->sum('subtotal');

                return [
                    'no_rko' => $rko->no_rko,
                    'periode' => $rko->periode,
                    'sumber_dana' => $rko->sumberDana?->nama ?? '—',
                    'total_rko' => $totalRko,
                    'realisasi_po' => $realisasi,
                    'selisih' => $totalRko - $realisasi,
                    'persen' => $totalRko > 0 ? round($realisasi / $totalRko * 100, 1) : 0,
                ];
            })
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
