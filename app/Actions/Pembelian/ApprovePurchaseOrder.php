<?php

namespace App\Actions\Pembelian;

use App\Actions\Anggaran\ValidatePagu;
use App\Enums\StatusPurchaseOrder;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApprovePurchaseOrder
{
    public function __construct(private readonly ValidatePagu $validatePagu) {}

    /**
     * Menyetujui PO sambil memeriksa pagu. Pagu yang terlampaui hanya menjadi
     * peringatan; approval tetap dapat dilanjutkan dengan alasan override.
     */
    public function handle(PurchaseOrder $po, ?int $userId = null, ?string $alasanOverride = null): PurchaseOrder
    {
        if ($po->status !== StatusPurchaseOrder::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Hanya PO berstatus draft yang dapat disetujui.',
            ]);
        }

        $metrics = $this->validatePagu->handle(
            (int) $po->tanggal_po->year,
            $po->sumber_dana_id,
            0,
            (float) $po->total,
            $po->id,
        );

        $alasanOverride = trim((string) $alasanOverride);

        if ($metrics['exceeded'] && $alasanOverride === '') {
            throw ValidationException::withMessages([
                'alasan_override_pagu' => sprintf(
                    'Pagu sumber dana terlampaui: pagu Rp %s, komitmen + realisasi Rp %s. Isi alasan override untuk melanjutkan.',
                    number_format($metrics['pagu'], 0, ',', '.'),
                    number_format($metrics['total_setelah'], 0, ',', '.'),
                ),
            ]);
        }

        $po->update([
            'approved_by' => $userId ?? auth()->id(),
            'status' => StatusPurchaseOrder::Dikirim,
            'alasan_override_pagu' => $metrics['exceeded'] ? $alasanOverride : null,
        ]);

        if ($metrics['exceeded']) {
            $user = $userId ? User::find($userId) : auth()->user();

            activity('anggaran')
                ->causedBy($user)
                ->performedOn($po)
                ->withProperties([
                    'pagu' => $metrics['pagu'],
                    'komitmen_bersih' => $metrics['komitmen_bersih'],
                    'realisasi' => $metrics['realisasi'],
                    'total_po' => (float) $po->total,
                    'alasan_override_pagu' => $alasanOverride,
                ])
                ->log('Override pagu pada approval purchase order');
        }

        return $po->refresh();
    }
}
