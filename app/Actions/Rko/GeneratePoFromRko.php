<?php

namespace App\Actions\Rko;

use App\Enums\StatusPurchaseOrder;
use App\Enums\StatusRko;
use App\Models\PurchaseOrder;
use App\Models\Rko;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeneratePoFromRko
{
    /** @return Collection<int, PurchaseOrder> */
    public function handle(Rko $rko, ?int $userId = null): Collection
    {
        if ($rko->status !== StatusRko::Disetujui) {
            throw ValidationException::withMessages(['status' => 'RKO harus disetujui sebelum membuat PO.']);
        }

        if (! $rko->sumber_dana_id || ! $rko->supplier_id) {
            throw ValidationException::withMessages(['sumber_dana_id' => 'RKO harus memiliki sumber dana dan supplier.']);
        }

        return DB::transaction(function () use ($rko, $userId) {
            $items = $rko->items()->with('obat')->where('kebutuhan_bersih', '>', 0)->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Tidak ada kebutuhan bersih untuk dibuat PO.']);
            }

            $prefix = 'PO-'.now()->format('Y').'-';
            $last = PurchaseOrder::where('no_po', 'like', $prefix.'%')->orderByDesc('id')->value('no_po');
            $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

            $po = PurchaseOrder::create([
                'no_po' => $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $rko->supplier_id,
                'sumber_dana_id' => $rko->sumber_dana_id,
                'tanggal_po' => now()->toDateString(),
                'status' => StatusPurchaseOrder::Draft,
                'total' => 0,
                'user_id' => $userId ?? auth()->id(),
                'catatan' => 'Draft PO dari '.$rko->no_rko,
            ]);

            $total = 0;
            foreach ($items as $item) {
                $jumlah = (int) ceil((float) $item->kebutuhan_bersih);
                $harga = (float) $item->harga_estimasi;
                $subtotal = $jumlah * $harga;

                $po->items()->create([
                    'obat_id' => $item->obat_id,
                    'jumlah' => $jumlah,
                    'harga_beli' => $harga,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $po->update(['total' => $total]);

            return collect([$po->load('items')]);
        });
    }
}
