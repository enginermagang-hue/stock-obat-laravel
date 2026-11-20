<?php

namespace App\Actions\Permintaan;

use App\Enums\SumberPermintaan;
use App\Enums\StatusPermintaan;
use App\Models\PermintaanObat;
use App\Scopes\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePermintaanMandiri
{
    /**
     * @param  array<int, array{obat_id:int,jumlah_diminta:int,keterangan?:?string}>  $items
     */
    public function handle(
        int $facilityId,
        array $items,
        ?string $periodeAwal = null,
        ?string $periodeAkhir = null,
        ?int $userId = null,
    ): PermintaanObat {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Minimal satu item permintaan.']);
        }

        return DB::transaction(function () use ($facilityId, $items, $periodeAwal, $periodeAkhir) {
            $permintaan = PermintaanObat::withoutGlobalScope(FacilityScope::class)->create([
                'no_permintaan' => $this->nextNumber(),
                'facility_id' => $facilityId,
                'sumber' => SumberPermintaan::Mandiri,
                'periode_awal' => $periodeAwal,
                'periode_akhir' => $periodeAkhir,
                'tanggal' => now()->toDateString(),
                'status' => StatusPermintaan::Draft,
            ]);

            foreach ($items as $item) {
                if ((int) $item['jumlah_diminta'] < 1) {
                    continue;
                }

                $permintaan->items()->create([
                    'obat_id' => $item['obat_id'],
                    'jumlah_diminta' => (int) $item['jumlah_diminta'],
                    'keterangan' => $item['keterangan'] ?? null,
                ]);
            }

            if ($permintaan->items()->count() === 0) {
                throw ValidationException::withMessages(['items' => 'Jumlah diminta harus lebih dari nol.']);
            }

            return $permintaan->load('items.obat');
        });
    }

    private function nextNumber(): string
    {
        $prefix = 'PMSK-'.now()->format('Y').'-';
        $last = PermintaanObat::withoutGlobalScope(FacilityScope::class)
            ->where('no_permintaan', 'like', $prefix.'%')
            ->orderByDesc('id')->value('no_permintaan');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
