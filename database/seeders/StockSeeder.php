<?php

namespace Database\Seeders;

use App\Enums\TipeMutasi;
use App\Enums\TipeReferensi;
use App\Models\BatchObat;
use App\Models\Obat;
use App\Services\Stock\StockLedgerService;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(StockLedgerService::class);
        $obat = Obat::orderBy('id')->take(5)->get();

        foreach ($obat as $index => $item) {
            $batch = BatchObat::firstOrCreate(
                ['obat_id' => $item->id, 'no_batch' => 'SEED-'.str_pad((string) $item->id, 3, '0', STR_PAD_LEFT)],
                [
                    'tanggal_produksi' => now()->subMonths(6)->toDateString(),
                    'tanggal_kedaluwarsa' => now()->addDays(30 + ($index * 120))->toDateString(),
                    'jumlah_awal' => 0,
                    'jumlah_sisa' => 0,
                    'harga_beli' => $item->harga_satuan_terakhir,
                    'lokasi_rak' => 'Rak '.chr(65 + $index).'-0'.($index + 1),
                    'aktif' => true,
                ],
            );

            if ($batch->ledger()->doesntExist()) {
                $service->record(
                    $batch,
                    TipeMutasi::Masuk,
                    100 + ($index * 40),
                    TipeReferensi::Penerimaan,
                    0,
                    null,
                    'Stok awal seeder',
                );
            }
        }
    }
}
