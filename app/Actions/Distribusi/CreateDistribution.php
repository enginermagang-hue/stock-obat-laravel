<?php

namespace App\Actions\Distribusi;

use App\Enums\StatusDistribusi;
use App\Enums\StatusPermintaan;
use App\Models\BatchObat;
use App\Models\Distribution;
use App\Models\PermintaanObat;
use App\Scopes\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDistribution
{
    public function __construct(private readonly SelectBatchFefo $selectBatchFefo) {}

    /**
     * @param  array<int, array{obat_id:int,jumlah:int,permintaan_item_id?:?int,keterangan?:?string}>  $items
     */
    public function handle(
        int $facilityId,
        array $items,
        ?int $permintaanId = null,
        ?int $userId = null,
        ?string $tanggal = null,
        ?string $catatan = null,
    ): Distribution {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Minimal satu item distribusi.']);
        }

        return DB::transaction(function () use ($facilityId, $items, $permintaanId, $userId, $tanggal, $catatan) {
            $permintaan = null;

            if ($permintaanId !== null) {
                $permintaan = PermintaanObat::withoutGlobalScope(FacilityScope::class)
                    ->whereKey($permintaanId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $permintaan->facility_id !== $facilityId) {
                    throw ValidationException::withMessages([
                        'permintaan_id' => 'Permintaan bukan milik fasilitas tujuan distribusi.',
                    ]);
                }

                if (! in_array($permintaan->status, [StatusPermintaan::Disetujui, StatusPermintaan::Sebagian, StatusPermintaan::Diproses], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Permintaan belum disetujui sehingga belum dapat didistribusikan.',
                    ]);
                }
            }

            $distribution = Distribution::create([
                'no_surat' => $this->nextNumber(),
                'facility_id' => $facilityId,
                'permintaan_id' => $permintaanId,
                'tanggal' => $tanggal ?? now()->toDateString(),
                'status' => StatusDistribusi::Draft,
                'user_id' => $userId ?? auth()->id(),
                'catatan' => $catatan,
            ]);

            foreach ($items as $item) {
                $jumlah = (int) ($item['jumlah'] ?? 0);

                if ($jumlah < 1) {
                    continue;
                }

                $obatId = (int) $item['obat_id'];
                $allocations = $this->selectBatchFefo->handle($obatId, $jumlah);

                foreach ($allocations as $allocation) {
                    /** @var BatchObat $batch */
                    $batch = $allocation['batch'];
                    $harga = (float) $batch->harga_beli;

                    $distribution->items()->create([
                        'permintaan_item_id' => $item['permintaan_item_id'] ?? null,
                        'obat_id' => $obatId,
                        'batch_id' => $batch->id,
                        'jumlah' => $allocation['jumlah'],
                        'harga_satuan' => $harga,
                        'subtotal' => $allocation['jumlah'] * $harga,
                        'keterangan' => $item['keterangan'] ?? null,
                    ]);
                }
            }

            if ($distribution->items()->count() === 0) {
                throw ValidationException::withMessages(['items' => 'Tidak ada item yang dapat didistribusikan.']);
            }

            if ($permintaan !== null) {
                $permintaan->update(['status' => StatusPermintaan::Diproses]);
            }

            return $distribution->load('items.obat', 'items.batch', 'facility', 'permintaan');
        });
    }

    private function nextNumber(): string
    {
        $prefix = 'SJ-'.now()->format('Y').'-';
        $last = Distribution::withoutGlobalScope(FacilityScope::class)
            ->where('no_surat', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('no_surat');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
