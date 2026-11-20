<?php

namespace App\Actions\Stock;

use App\Enums\StatusPurchaseOrder;
use App\Enums\TipeMutasi;
use App\Enums\TipeReferensi;
use App\Models\BatchObat;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Services\Stock\StockLedgerService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivePurchaseOrder
{
    public function __construct(private readonly StockLedgerService $ledger) {}

    /**
     * @param  array<int, array{po_item_id:int,no_batch:string,tanggal_produksi?:?string,tanggal_kedaluwarsa:string,jumlah:int,harga_beli?:numeric,lokasi_rak?:?string}>  $items
     */
    public function handle(
        PurchaseOrder $po,
        array $items,
        ?int $userId = null,
        ?CarbonInterface $tanggal = null,
        ?string $catatan = null,
    ): GoodsReceipt {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Minimal satu item penerimaan.']);
        }

        return DB::transaction(function () use ($po, $items, $userId, $tanggal, $catatan) {
            $locked = PurchaseOrder::query()->whereKey($po->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [StatusPurchaseOrder::Dibatalkan, StatusPurchaseOrder::Diterima], true)) {
                throw ValidationException::withMessages(['status' => 'PO tidak dapat diterima pada status ini.']);
            }

            $receipt = GoodsReceipt::create([
                'no_penerimaan' => $this->nextNumber(),
                'po_id' => $locked->id,
                'supplier_id' => $locked->supplier_id,
                'sumber_dana_id' => $locked->sumber_dana_id,
                'tanggal_terima' => $tanggal ?? now(),
                'user_id' => $userId ?? auth()->id(),
                'catatan' => $catatan,
            ]);

            foreach ($items as $item) {
                $poItem = $locked->items()->whereKey($item['po_item_id'])->firstOrFail();
                $jumlah = (int) $item['jumlah'];
                $sisa = $poItem->jumlah - $poItem->jumlah_diterima;

                if ($jumlah <= 0 || $jumlah > $sisa) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah penerimaan untuk {$poItem->obat->nama} melebihi sisa PO.",
                    ]);
                }

                $harga = isset($item['harga_beli']) ? (float) $item['harga_beli'] : (float) $poItem->harga_beli;

                $receiptItem = $receipt->items()->create([
                    'po_item_id' => $poItem->id,
                    'obat_id' => $poItem->obat_id,
                    'no_batch' => $item['no_batch'],
                    'tanggal_produksi' => $item['tanggal_produksi'] ?? null,
                    'tanggal_kedaluwarsa' => $item['tanggal_kedaluwarsa'],
                    'jumlah' => $jumlah,
                    'harga_beli' => $harga,
                    'lokasi_rak' => $item['lokasi_rak'] ?? null,
                    'sumber_dana_id' => $locked->sumber_dana_id,
                    'subtotal' => $jumlah * $harga,
                ]);

                $batch = BatchObat::firstOrCreate(
                    ['obat_id' => $poItem->obat_id, 'no_batch' => $item['no_batch']],
                    [
                        'tanggal_produksi' => $item['tanggal_produksi'] ?? null,
                        'tanggal_kedaluwarsa' => $item['tanggal_kedaluwarsa'],
                        'jumlah_awal' => 0,
                        'jumlah_sisa' => 0,
                        'harga_beli' => $harga,
                        'lokasi_rak' => $item['lokasi_rak'] ?? null,
                        'sumber_dana_id' => $locked->sumber_dana_id,
                        'supplier_id' => $locked->supplier_id,
                        'aktif' => true,
                    ],
                );

                $batch->increment('jumlah_awal', $jumlah);

                $this->ledger->record(
                    $batch,
                    TipeMutasi::Masuk,
                    $jumlah,
                    TipeReferensi::Penerimaan,
                    $receipt->id,
                    $userId,
                    'Penerimaan '.$receipt->no_penerimaan,
                    $tanggal,
                );

                $poItem->increment('jumlah_diterima', $jumlah);
            }

            $locked->refresh()->load('items');
            $complete = $locked->items->every(fn ($item) => $item->jumlah_diterima >= $item->jumlah);

            $locked->update([
                'status' => $complete ? StatusPurchaseOrder::Diterima : StatusPurchaseOrder::DiterimaSebagian,
            ]);

            return $receipt->load('items');
        });
    }

    private function nextNumber(): string
    {
        $prefix = 'GR-'.now()->format('Y').'-';
        $last = GoodsReceipt::where('no_penerimaan', 'like', $prefix.'%')
            ->orderByDesc('id')->value('no_penerimaan');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
