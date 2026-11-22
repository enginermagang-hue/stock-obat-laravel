<?php

namespace App\Actions\Distribusi;

use App\Enums\StatusDistribusi;
use App\Enums\StatusItemPermintaan;
use App\Enums\StatusPermintaan;
use App\Models\Distribution;
use App\Models\PermintaanObat;
use App\Scopes\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDistribution
{
    public function handle(Distribution $distribution, ?string $alasan = null): Distribution
    {
        return DB::transaction(function () use ($distribution, $alasan) {
            $locked = Distribution::withoutGlobalScope(FacilityScope::class)
                ->whereKey($distribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === StatusDistribusi::Dibatalkan) {
                return $locked;
            }

            if (! in_array($locked->status, [StatusDistribusi::Draft, StatusDistribusi::Disetujui], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Surat jalan yang sudah dikirim tidak dapat dibatalkan.',
                ]);
            }

            $locked->update([
                'status' => StatusDistribusi::Dibatalkan,
                'catatan' => $alasan ?: $locked->catatan,
            ]);

            $this->revertPermintaan($locked);

            return $locked->refresh();
        });
    }

    /**
     * Kembalikan permintaan ke status setuju/sebagian bila tidak ada lagi
     * surat jalan aktif untuk permintaan tersebut.
     */
    private function revertPermintaan(Distribution $distribution): void
    {
        if ($distribution->permintaan_id === null) {
            return;
        }

        $active = Distribution::withoutGlobalScope(FacilityScope::class)
            ->where('permintaan_id', $distribution->permintaan_id)
            ->where('status', '!=', StatusDistribusi::Dibatalkan->value)
            ->exists();

        if ($active) {
            return;
        }

        $permintaan = PermintaanObat::withoutGlobalScope(FacilityScope::class)
            ->with('items')
            ->find($distribution->permintaan_id);

        if ($permintaan === null) {
            return;
        }

        $hasPartial = $permintaan->items->contains(
            fn ($item) => in_array($item->status_item, [StatusItemPermintaan::Sebagian, StatusItemPermintaan::Backorder], true)
        );

        $permintaan->update([
            'status' => $hasPartial ? StatusPermintaan::Sebagian : StatusPermintaan::Disetujui,
        ]);
    }
}
