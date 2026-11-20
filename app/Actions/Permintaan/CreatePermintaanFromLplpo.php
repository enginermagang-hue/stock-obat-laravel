<?php

namespace App\Actions\Permintaan;

use App\Enums\SumberPermintaan;
use App\Enums\StatusLplpo;
use App\Enums\StatusPermintaan;
use App\Models\Lplpo;
use App\Models\PermintaanObat;
use App\Scopes\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePermintaanFromLplpo
{
    public function handle(Lplpo $lplpo, ?int $userId = null): PermintaanObat
    {
        if ($lplpo->status !== StatusLplpo::Diverifikasi) {
            throw ValidationException::withMessages(['status' => 'LPLPO harus diverifikasi sebelum membuat permintaan.']);
        }

        return DB::transaction(function () use ($lplpo, $userId) {
            $exists = PermintaanObat::withoutGlobalScope(FacilityScope::class)
                ->where('lplpo_id', $lplpo->id)->exists();

            if ($exists) {
                throw ValidationException::withMessages(['lplpo' => 'Permintaan untuk LPLPO ini sudah dibuat.']);
            }

            $periodeAwal = now()->setDate($lplpo->tahun, $lplpo->bulan, 1)->startOfMonth();
            $periodeAkhir = (clone $periodeAwal)->endOfMonth();

            $permintaan = PermintaanObat::withoutGlobalScope(FacilityScope::class)->create([
                'no_permintaan' => $this->nextNumber($lplpo->tahun),
                'facility_id' => $lplpo->facility_id,
                'lplpo_id' => $lplpo->id,
                'sumber' => SumberPermintaan::Lplpo,
                'periode_awal' => $periodeAwal->toDateString(),
                'periode_akhir' => $periodeAkhir->toDateString(),
                'tanggal' => now()->toDateString(),
                'status' => StatusPermintaan::Draft,
            ]);

            $lplpo->items()->where('jumlah_diminta', '>', 0)->each(function ($item) use ($permintaan) {
                $permintaan->items()->create([
                    'obat_id' => $item->obat_id,
                    'jumlah_diminta' => $item->jumlah_diminta,
                ]);
            });

            if ($permintaan->items()->count() === 0) {
                throw ValidationException::withMessages(['items' => 'Tidak ada item dengan jumlah diminta.']);
            }

            return $permintaan->load('items.obat');
        });
    }

    private function nextNumber(int $tahun): string
    {
        $prefix = 'PMSK-'.$tahun.'-';
        $last = PermintaanObat::withoutGlobalScope(FacilityScope::class)
            ->where('no_permintaan', 'like', $prefix.'%')
            ->orderByDesc('id')->value('no_permintaan');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
