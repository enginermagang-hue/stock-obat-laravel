<?php

namespace App\Services\Stock;

use App\Enums\TipeMutasi;
use App\Enums\TipeReferensi;
use App\Models\BatchObat;
use App\Models\StockLedger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockLedgerService
{
    public function record(
        BatchObat $batch,
        TipeMutasi $tipe,
        int $jumlah,
        TipeReferensi $tipeReferensi,
        int $referensiId,
        ?int $userId = null,
        ?string $catatan = null,
        ?CarbonInterface $tanggal = null,
    ): StockLedger {
        if ($jumlah <= 0) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah mutasi harus lebih dari nol.']);
        }

        return DB::transaction(function () use ($batch, $tipe, $jumlah, $tipeReferensi, $referensiId, $userId, $catatan, $tanggal) {
            $locked = BatchObat::query()->whereKey($batch->getKey())->lockForUpdate()->firstOrFail();

            $sebelum = $locked->jumlah_sisa;
            $sesudah = $tipe === TipeMutasi::Masuk ? $sebelum + $jumlah : $sebelum - $jumlah;

            if ($sesudah < 0) {
                throw ValidationException::withMessages(['jumlah' => 'Stok batch tidak mencukupi.']);
            }

            $locked->update(['jumlah_sisa' => $sesudah]);

            return StockLedger::create([
                'batch_id' => $locked->id,
                'tipe' => $tipe,
                'jumlah' => $jumlah,
                'jumlah_sebelum' => $sebelum,
                'jumlah_sesudah' => $sesudah,
                'tipe_referensi' => $tipeReferensi,
                'referensi_id' => $referensiId,
                'tanggal_transaksi' => $tanggal ?? now(),
                'user_id' => $userId ?? auth()->id(),
                'catatan' => $catatan,
                'created_at' => now(),
            ]);
        });
    }
}
