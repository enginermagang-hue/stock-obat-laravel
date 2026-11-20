<?php

namespace App\Actions\Opname;

use App\Actions\Stock\ProcessStockAdjustment;
use App\Enums\StatusOpname;
use App\Enums\StatusPenyesuaian;
use App\Enums\TipePenyesuaian;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAdjustmentFromOpname
{
    public function __construct(private readonly ProcessStockAdjustment $processAdjustment) {}

    public function handle(StockOpname $opname, ?int $approverId = null): StockAdjustment
    {
        return DB::transaction(function () use ($opname, $approverId) {
            $locked = StockOpname::query()->whereKey($opname->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [StatusOpname::Diverifikasi, StatusOpname::Disetujui], true)) {
                throw ValidationException::withMessages(['status' => 'Opname belum diverifikasi.']);
            }

            $adjustment = StockAdjustment::create([
                'no_penyesuaian' => $this->nextNumber($locked->periode_tahun),
                'tipe' => TipePenyesuaian::Opname,
                'tanggal' => now()->toDateString(),
                'status' => StatusPenyesuaian::Draft,
                'stock_opname_id' => $locked->id,
                'user_id' => $approverId ?? auth()->id(),
                'catatan' => 'Penyesuaian dari opname '.$locked->no_opname,
            ]);

            $locked->items()->where('selisih', '!=', 0)->each(function ($item) use ($adjustment) {
                $adjustment->items()->create([
                    'batch_id' => $item->batch_id,
                    'obat_id' => $item->batch->obat_id,
                    'jumlah' => $item->selisih,
                    'alasan' => $item->keterangan ?? 'selisih opname',
                    'nilai' => $item->selisih * $item->harga_beli,
                ]);
            });

            $this->processAdjustment->handle($adjustment, $approverId);

            $locked->update([
                'status' => StatusOpname::Selesai,
                'disetujui_oleh' => $approverId ?? auth()->id(),
            ]);

            return $adjustment->refresh()->load('items');
        });
    }

    private function nextNumber(int $tahun): string
    {
        $prefix = 'ADJ-'.$tahun.'-';
        $last = StockAdjustment::where('no_penyesuaian', 'like', $prefix.'%')->orderByDesc('id')->value('no_penyesuaian');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
