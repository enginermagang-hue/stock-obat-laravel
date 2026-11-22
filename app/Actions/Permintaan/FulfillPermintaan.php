<?php

namespace App\Actions\Permintaan;

use App\Actions\Distribusi\CreateDistribution;
use App\Enums\StatusDistribusi;
use App\Enums\StatusItemPermintaan;
use App\Enums\StatusPermintaan;
use App\Models\Distribution;
use App\Models\DistributionItem;
use App\Models\PermintaanObat;
use App\Scopes\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillPermintaan
{
    public function __construct(private readonly CreateDistribution $createDistribution) {}

    /**
     * Buat surat jalan (draft) dari permintaan yang sudah disetujui, memakai FEFO.
     *
     * @param  array<int, int>|null  $itemIds  Batasi item permintaan yang dipenuhi.
     */
    public function handle(
        PermintaanObat $permintaan,
        ?array $itemIds = null,
        ?int $userId = null,
        ?string $tanggal = null,
        ?string $catatan = null,
    ): Distribution {
        return DB::transaction(function () use ($permintaan, $itemIds, $userId, $tanggal, $catatan) {
            $locked = PermintaanObat::withoutGlobalScope(FacilityScope::class)
                ->whereKey($permintaan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, [StatusPermintaan::Disetujui, StatusPermintaan::Sebagian, StatusPermintaan::Diproses], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Permintaan belum disetujui sehingga belum dapat didistribusikan.',
                ]);
            }

            $items = [];

            foreach ($locked->items as $item) {
                if ($itemIds !== null && ! in_array($item->id, $itemIds, true)) {
                    continue;
                }

                if (in_array($item->status_item, [StatusItemPermintaan::Ditolak, StatusItemPermintaan::Backorder], true)) {
                    continue;
                }

                $approved = (int) ($item->jumlah_disetujui ?? $item->jumlah_diminta);

                if ($approved < 1) {
                    continue;
                }

                $fulfilled = DistributionItem::query()
                    ->where('permintaan_item_id', $item->id)
                    ->whereHas('distribution', fn ($query) => $query
                        ->withoutGlobalScope(FacilityScope::class)
                        ->where('status', '!=', StatusDistribusi::Dibatalkan->value))
                    ->sum('jumlah');

                $remaining = $approved - (int) $fulfilled;

                if ($remaining < 1) {
                    continue;
                }

                $items[] = [
                    'obat_id' => $item->obat_id,
                    'jumlah' => $remaining,
                    'permintaan_item_id' => $item->id,
                ];
            }

            if ($items === []) {
                throw ValidationException::withMessages(['items' => 'Tidak ada item yang masih dapat dipenuhi.']);
            }

            return $this->createDistribution->handle(
                (int) $locked->facility_id,
                $items,
                $locked->id,
                $userId,
                $tanggal,
                $catatan,
            );
        });
    }
}
