<?php

namespace App\Actions\Stock;

use App\Enums\StatusPenyesuaian;
use App\Enums\TipeMutasi;
use App\Enums\TipeReferensi;
use App\Models\BatchObat;
use App\Models\StockAdjustment;
use App\Services\Stock\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessStockAdjustment
{
    public function __construct(private readonly StockLedgerService $ledger) {}

    public function handle(StockAdjustment $adjustment, ?int $approverId = null): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $approverId) {
            $locked = StockAdjustment::query()->whereKey($adjustment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === StatusPenyesuaian::Diproses) {
                return $locked->load('items');
            }

            if ($locked->status === StatusPenyesuaian::Dibatalkan) {
                throw ValidationException::withMessages(['status' => 'Penyesuaian sudah dibatalkan.']);
            }

            $locked->load('items');

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Penyesuaian tidak memiliki item.']);
            }

            $tipeReferensi = $locked->stock_opname_id
                ? TipeReferensi::Opname
                : TipeReferensi::Penyesuaian;

            foreach ($locked->items as $item) {
                $batch = BatchObat::findOrFail($item->batch_id);
                $mutasi = $item->jumlah >= 0 ? TipeMutasi::Masuk : TipeMutasi::Keluar;

                $this->ledger->record(
                    $batch,
                    $mutasi,
                    abs($item->jumlah),
                    $tipeReferensi,
                    $locked->id,
                    $approverId,
                    $item->alasan,
                );
            }

            $locked->update([
                'status' => StatusPenyesuaian::Diproses,
                'approved_by' => $approverId ?? auth()->id(),
            ]);

            return $locked->load('items');
        });
    }
}
