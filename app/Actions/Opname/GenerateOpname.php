<?php

namespace App\Actions\Opname;

use App\Enums\ModeOpname;
use App\Enums\StatusOpname;
use App\Enums\TipeOpname;
use App\Models\BatchObat;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;

class GenerateOpname
{
    public function handle(
        int $bulan,
        int $tahun,
        TipeOpname $tipe = TipeOpname::Berkala,
        ModeOpname $mode = ModeOpname::Buta,
        ?int $facilityId = null,
        ?int $petugasId = null,
        ?string $catatan = null,
    ): StockOpname {
        return DB::transaction(function () use ($bulan, $tahun, $tipe, $mode, $facilityId, $petugasId, $catatan) {
            $opname = StockOpname::create([
                'no_opname' => $this->nextNumber($tahun),
                'tanggal' => now()->toDateString(),
                'periode_bulan' => $bulan,
                'periode_tahun' => $tahun,
                'tipe' => $tipe,
                'mode' => $mode,
                'facility_id' => $facilityId,
                'status' => StatusOpname::Draft,
                'petugas_id' => $petugasId ?? auth()->id(),
                'catatan' => $catatan,
                'versi' => 1,
            ]);

            BatchObat::query()
                ->where('jumlah_sisa', '>', 0)
                ->orderBy('tanggal_kedaluwarsa')
                ->each(function (BatchObat $batch) use ($opname) {
                    $opname->items()->create([
                        'batch_id' => $batch->id,
                        'stok_sistem' => $batch->jumlah_sisa,
                        'harga_beli' => $batch->harga_beli,
                    ]);
                });

            return $opname->load('items');
        });
    }

    private function nextNumber(int $tahun): string
    {
        $prefix = 'OPN-'.$tahun.'-';
        $last = StockOpname::where('no_opname', 'like', $prefix.'%')->orderByDesc('id')->value('no_opname');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
