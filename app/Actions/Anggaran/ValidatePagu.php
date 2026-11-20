<?php

namespace App\Actions\Anggaran;

use App\Enums\StatusPurchaseOrder;
use App\Models\Anggaran;
use Illuminate\Support\Facades\DB;

class ValidatePagu
{
    /**
     * @return array{pagu:float,komitmen:float,realisasi:float,komitmen_bersih:float,sisa:float,total_setelah:float,exceeded:bool}
     */
    public function handle(
        int $tahun,
        int $sumberDanaId,
        int $facilityId,
        float $amount = 0,
        ?int $excludePoId = null,
    ): array {
        $pagu = (float) Anggaran::query()
            ->where('tahun', $tahun)
            ->where('sumber_dana_id', $sumberDanaId)
            ->where('facility_id', $facilityId)
            ->sum('jumlah_anggaran');

        $komitmen = 0.0;

        if ($facilityId === 0) {
            $komitmen = (float) DB::table('purchase_order')
                ->where('sumber_dana_id', $sumberDanaId)
                ->whereYear('tanggal_po', $tahun)
                ->where('status', '!=', StatusPurchaseOrder::Dibatalkan->value)
                ->when($excludePoId, fn ($q) => $q->where('id', '!=', $excludePoId))
                ->sum('total');
        }

        $realisasi = (float) DB::table('goods_receipt_item')
            ->join('goods_receipt', 'goods_receipt.id', '=', 'goods_receipt_item.receipt_id')
            ->join('purchase_order', 'purchase_order.id', '=', 'goods_receipt.po_id')
            ->where('purchase_order.sumber_dana_id', $sumberDanaId)
            ->whereYear('goods_receipt.tanggal_terima', $tahun)
            ->when($facilityId !== 0, fn ($q) => $q->whereRaw('1 = 0'))
            ->sum('goods_receipt_item.subtotal');

        $komitmenBersih = max(0, $komitmen - $realisasi);
        $totalSetelah = $komitmenBersih + $realisasi + $amount;
        $sisa = $pagu - $komitmenBersih - $realisasi;

        return [
            'pagu' => $pagu,
            'komitmen' => $komitmen,
            'realisasi' => $realisasi,
            'komitmen_bersih' => $komitmenBersih,
            'sisa' => $sisa,
            'total_setelah' => $totalSetelah,
            'exceeded' => $pagu > 0 && $totalSetelah > $pagu,
        ];
    }
}
