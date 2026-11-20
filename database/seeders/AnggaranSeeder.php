<?php

namespace Database\Seeders;

use App\Actions\Anggaran\SimpanAnggaran;
use App\Models\Anggaran;
use App\Models\Facility;
use App\Models\SumberDana;
use Illuminate\Database\Seeder;

class AnggaranSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = (int) now()->year;
        $action = app(SimpanAnggaran::class);

        $pagu = [
            'APBN' => 1_200_000_000,
            'APBD_KAB' => 750_000_000,
            'BOK' => 180_000_000,
        ];

        foreach ($pagu as $kode => $jumlah) {
            $sumberDana = SumberDana::where('kode', $kode)->first();

            if (! $sumberDana) {
                continue;
            }

            $exists = Anggaran::where('tahun', $tahun)
                ->where('sumber_dana_id', $sumberDana->id)
                ->where('facility_id', Anggaran::RS_FACILITY_ID)
                ->exists();

            if (! $exists) {
                $action->handle($tahun, $sumberDana->id, Anggaran::RS_FACILITY_ID, $jumlah, 'Pagu awal '.$tahun);
            }
        }

        $bok = SumberDana::where('kode', 'BOK')->first();

        if ($bok) {
            Facility::where('jenis', 'puskesmas')
                ->orderBy('kode')
                ->take(3)
                ->get()
                ->each(function (Facility $facility) use ($action, $tahun, $bok) {
                    $exists = Anggaran::where('tahun', $tahun)
                        ->where('sumber_dana_id', $bok->id)
                        ->where('facility_id', $facility->id)
                        ->exists();

                    if (! $exists) {
                        $action->handle(
                            $tahun,
                            $bok->id,
                            $facility->id,
                            60_000_000,
                            'Pagu BOK '.$tahun,
                        );
                    }
                });
        }
    }
}
