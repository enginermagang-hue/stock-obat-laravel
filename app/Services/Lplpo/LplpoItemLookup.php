<?php

namespace App\Services\Lplpo;

use App\Enums\StatusDistribusi;
use App\Enums\StatusLplpo;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung angka otomatis untuk item LPLPO:
 * stok awal dari LPLPO terverifikasi terakhir dan pemasukan dari distribusi periode berjalan.
 */
class LplpoItemLookup
{
    /** @return array{stok_awal:int,pemasukan:int,pemakaian:int,stok_akhir:int} */
    public function handle(int $facilityId, int $obatId, int $bulan, int $tahun): array
    {
        $stokAwal = $this->previousStock($facilityId, $obatId, $bulan, $tahun);
        $pemasukan = $this->incoming($facilityId, $obatId, $bulan, $tahun);
        $pemakaian = 0;

        return [
            'stok_awal' => $stokAwal,
            'pemasukan' => $pemasukan,
            'pemakaian' => $pemakaian,
            'stok_akhir' => max(0, $stokAwal + $pemasukan - $pemakaian),
        ];
    }

    public function previousStock(int $facilityId, int $obatId, int $bulan, int $tahun): int
    {
        return (int) DB::table('lplpo_item')
            ->join('lplpo', 'lplpo.id', '=', 'lplpo_item.lplpo_id')
            ->where('lplpo.facility_id', $facilityId)
            ->where('lplpo.status', StatusLplpo::Diverifikasi->value)
            ->where('lplpo_item.obat_id', $obatId)
            ->where(function ($query) use ($bulan, $tahun) {
                $query->where('lplpo.tahun', '<', $tahun)
                    ->orWhere(fn ($q) => $q->where('lplpo.tahun', $tahun)->where('lplpo.bulan', '<', $bulan));
            })
            ->orderByDesc('lplpo.tahun')
            ->orderByDesc('lplpo.bulan')
            ->orderByDesc('lplpo.versi')
            ->value('lplpo_item.stok_akhir');
    }

    /**
     * Pemasukan barang ke fasilitas pada periode berjalan (distribusi yang dikirim/diterima).
     */
    public function incoming(int $facilityId, int $obatId, int $bulan, int $tahun): int
    {
        return (int) DB::table('distribution_item')
            ->join('distribution', 'distribution.id', '=', 'distribution_item.distribution_id')
            ->where('distribution.facility_id', $facilityId)
            ->whereIn('distribution.status', [StatusDistribusi::Dikirim->value, StatusDistribusi::Diterima->value])
            ->where('distribution_item.obat_id', $obatId)
            ->whereYear('distribution.tanggal', $tahun)
            ->whereMonth('distribution.tanggal', $bulan)
            ->sum('distribution_item.jumlah');
    }
}
