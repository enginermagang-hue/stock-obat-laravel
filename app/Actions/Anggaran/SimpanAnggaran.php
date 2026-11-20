<?php

namespace App\Actions\Anggaran;

use App\Models\Anggaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanAnggaran
{
    /**
     * Menyimpan atau merevisi pagu. Revisi menaikkan versi, bukan membuat baris baru,
     * karena kombinasi (tahun, sumber dana, unit) bersifat unik.
     */
    public function handle(
        int $tahun,
        int $sumberDanaId,
        int $facilityId,
        float $jumlahAnggaran,
        ?string $keterangan = null,
        bool $allowUpdate = true,
    ): Anggaran {
        if ($jumlahAnggaran < 0) {
            throw ValidationException::withMessages([
                'jumlah_anggaran' => 'Jumlah anggaran tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($tahun, $sumberDanaId, $facilityId, $jumlahAnggaran, $keterangan, $allowUpdate) {
            $existing = Anggaran::query()
                ->where('tahun', $tahun)
                ->where('sumber_dana_id', $sumberDanaId)
                ->where('facility_id', $facilityId)
                ->lockForUpdate()
                ->first();

            if ($existing && ! $allowUpdate) {
                throw ValidationException::withMessages([
                    'sumber_dana_id' => 'Pagu untuk tahun, sumber dana, dan unit ini sudah ada. Gunakan revisi.',
                ]);
            }

            if ($existing) {
                $existing->update([
                    'jumlah_anggaran' => $jumlahAnggaran,
                    'keterangan' => $keterangan,
                    'status' => 'aktif',
                    'versi' => $existing->versi + 1,
                ]);

                return $existing->refresh();
            }

            return Anggaran::create([
                'tahun' => $tahun,
                'sumber_dana_id' => $sumberDanaId,
                'facility_id' => $facilityId,
                'jumlah_anggaran' => $jumlahAnggaran,
                'status' => 'aktif',
                'versi' => 1,
                'keterangan' => $keterangan,
            ]);
        });
    }
}
