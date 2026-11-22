<?php

namespace App\Actions\Lplpo;

use App\Enums\StatusLplpo;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Scopes\FacilityScope;
use App\Services\Lplpo\LplpoItemLookup;
use Illuminate\Support\Facades\DB;

class GenerateLplpo
{
    public function __construct(private readonly LplpoItemLookup $lookup) {}

    /**
     * @param  array<int, array{obat_id:int,pemasukan?:int,pemakaian?:int,stok_akhir?:int,jumlah_diminta?:int,keterangan?:?string}>  $items
     */
    public function handle(
        int $facilityId,
        int $bulan,
        int $tahun,
        array $items,
        ?int $userId = null,
        ?string $catatan = null,
    ): Lplpo {
        return DB::transaction(function () use ($facilityId, $bulan, $tahun, $items, $catatan) {
            $draft = Lplpo::withoutGlobalScope(FacilityScope::class)
                ->where('facility_id', $facilityId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('status', StatusLplpo::Draft)
                ->first();

            $maxVersi = (int) Lplpo::withoutGlobalScope(FacilityScope::class)
                ->where('facility_id', $facilityId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->max('versi');

            if ($draft) {
                $lplpo = $draft;
                $lplpo->items()->delete();
            } else {
                $lplpo = Lplpo::withoutGlobalScope(FacilityScope::class)->create([
                    'no_lplpo' => $this->nextNumber($tahun),
                    'facility_id' => $facilityId,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'status' => StatusLplpo::Draft,
                    'versi' => $maxVersi + 1,
                    'catatan' => $catatan,
                ]);
            }

            foreach ($items as $row) {
                $obat = Obat::findOrFail($row['obat_id']);
                $stokAwal = $this->lookup->previousStock($facilityId, $obat->id, $bulan, $tahun);
                $pemasukan = (int) ($row['pemasukan'] ?? 0);
                $pemakaian = (int) ($row['pemakaian'] ?? 0);

                $lplpo->items()->create([
                    'obat_id' => $obat->id,
                    'stok_awal' => $stokAwal,
                    'pemasukan' => $pemasukan,
                    'pemakaian' => $pemakaian,
                    'stok_akhir' => (int) ($row['stok_akhir'] ?? max(0, $stokAwal + $pemasukan - $pemakaian)),
                    'stok_akhir_sistem' => max(0, $stokAwal + $pemasukan - $pemakaian),
                    'stok_minimum' => $obat->stok_minimum,
                    'jumlah_diminta' => (int) ($row['jumlah_diminta'] ?? 0),
                    'keterangan' => $row['keterangan'] ?? null,
                ]);
            }

            return $lplpo->load('items.obat');
        });
    }

    private function nextNumber(int $tahun): string
    {
        $prefix = 'LPL-'.$tahun.'-';
        $last = Lplpo::withoutGlobalScope(FacilityScope::class)
            ->where('no_lplpo', 'like', $prefix.'%')
            ->orderByDesc('id')->value('no_lplpo');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
