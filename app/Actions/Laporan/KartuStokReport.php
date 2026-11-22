<?php

namespace App\Actions\Laporan;

use App\Enums\TipeMutasi;
use App\Models\StockLedger;

class KartuStokReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'tanggal' => 'Tanggal',
            'referensi' => 'Referensi',
            'batch' => 'Batch',
            'masuk' => 'Masuk',
            'keluar' => 'Keluar',
            'saldo' => 'Saldo',
            'catatan' => 'Catatan',
        ];

        $obatId = $params['obat_id'] ?? null;

        if (! $obatId) {
            return ['headings' => $headings, 'rows' => []];
        }

        $rows = StockLedger::query()
            ->with('batch')
            ->whereHas('batch', fn ($query) => $query->where('obat_id', $obatId))
            ->when($params['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal_transaksi', '>=', $from))
            ->when($params['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal_transaksi', '<=', $to))
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get()
            ->map(fn (StockLedger $ledger) => [
                'tanggal' => $ledger->tanggal_transaksi?->format('d-m-Y'),
                'referensi' => ucfirst($ledger->tipe_referensi->value).' #'.$ledger->referensi_id,
                'batch' => $ledger->batch?->no_batch,
                'masuk' => $ledger->tipe === TipeMutasi::Masuk ? $ledger->jumlah : null,
                'keluar' => $ledger->tipe === TipeMutasi::Keluar ? $ledger->jumlah : null,
                'saldo' => $ledger->jumlah_sesudah,
                'catatan' => $ledger->catatan,
            ])
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
