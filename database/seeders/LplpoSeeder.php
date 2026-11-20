<?php

namespace Database\Seeders;

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Actions\Lplpo\VerifyLplpo;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Scopes\FacilityScope;
use Illuminate\Database\Seeder;

class LplpoSeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::where('kode', 'PKM-007')->first();
        $obat = Obat::orderBy('id')->take(3)->get();

        if (! $facility || $obat->count() < 3) {
            return;
        }

        $previous = now()->subMonthNoOverflow();

        $exists = Lplpo::withoutGlobalScope(FacilityScope::class)
            ->where('facility_id', $facility->id)
            ->where('bulan', $previous->month)
            ->where('tahun', $previous->year)
            ->where('status', 'diverifikasi')
            ->exists();

        if (! $exists) {
            $lplpo = app(GenerateLplpo::class)->handle(
                $facility->id,
                $previous->month,
                $previous->year,
                [
                    ['obat_id' => $obat[0]->id, 'pemasukan' => 150, 'pemakaian' => 60, 'stok_akhir' => 90, 'jumlah_diminta' => 50],
                    ['obat_id' => $obat[1]->id, 'pemasukan' => 100, 'pemakaian' => 70, 'stok_akhir' => 30, 'jumlah_diminta' => 80],
                    ['obat_id' => $obat[2]->id, 'pemasukan' => 50, 'pemakaian' => 40, 'stok_akhir' => 10, 'jumlah_diminta' => 30],
                ],
                null,
                'Contoh LPLPO terverifikasi',
            );

            app(SubmitLplpo::class)->handle($lplpo);
            app(VerifyLplpo::class)->handle($lplpo->refresh(), true, null, 'Disetujui seeder');
        }

        $currentDraft = Lplpo::withoutGlobalScope(FacilityScope::class)
            ->where('facility_id', $facility->id)
            ->where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->exists();

        if (! $currentDraft) {
            app(GenerateLplpo::class)->handle(
                $facility->id,
                now()->month,
                now()->year,
                [
                    ['obat_id' => $obat[0]->id, 'pemasukan' => 0, 'pemakaian' => 25, 'stok_akhir' => 65, 'jumlah_diminta' => 40],
                    ['obat_id' => $obat[1]->id, 'pemasukan' => 0, 'pemakaian' => 15, 'stok_akhir' => 15, 'jumlah_diminta' => 60],
                    ['obat_id' => $obat[2]->id, 'pemasukan' => 0, 'pemakaian' => 5, 'stok_akhir' => 5, 'jumlah_diminta' => 25],
                ],
                null,
                'Contoh LPLPO draft',
            );
        }
    }
}
