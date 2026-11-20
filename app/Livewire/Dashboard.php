<?php

namespace App\Livewire;

use App\Actions\Anggaran\BuildLaporanAnggaranRealisasi;
use App\Enums\StatusLplpo;
use App\Enums\StatusPermintaan;
use App\Models\Anggaran;
use App\Models\BatchObat;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Models\StockLedger;
use App\Models\SumberDana;
use App\Models\Supplier;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $stokMenipis = Obat::query()
            ->whereRaw('(select coalesce(sum(jumlah_sisa), 0) from batch_obat where batch_obat.obat_id = obat.id) <= obat.stok_minimum')
            ->count();

        $tahun = (int) now()->year;
        $anggaran = null;
        $penyerapan = collect();

        if (auth()->user()->can('manage-anggaran')) {
            $anggaran = app(BuildLaporanAnggaranRealisasi::class)->handle($tahun, null, Anggaran::RS_FACILITY_ID);
            $penyerapan = $anggaran['rows']
                ->map(fn (array $row) => [
                    'nama' => $row['anggaran']->sumberDana?->nama ?? 'Tanpa nama',
                    'pagu' => $row['pagu'],
                    'serapan' => round((($row['realisasi'] + $row['komitmen_bersih']) / max(0.01, $row['pagu'])) * 100, 1),
                ])
                ->sortByDesc('serapan')
                ->take(4)
                ->values();
        }

        return view('livewire.dashboard', [
            'totalObat' => Obat::count(),
            'totalPuskesmas' => Facility::where('jenis', 'puskesmas')->count(),
            'totalSumberDana' => SumberDana::count(),
            'totalSupplier' => Supplier::count(),
            'totalBatch' => BatchObat::count(),
            'stokMenipis' => $stokMenipis,
            'expiring' => BatchObat::expiring(90)->count(),
            'mutasiHariIni' => StockLedger::whereDate('tanggal_transaksi', today())->count(),
            'menungguVerifikasi' => Lplpo::where('status', StatusLplpo::Dikirim)->count()
                + PermintaanObat::whereIn('status', [StatusPermintaan::Dikirim, StatusPermintaan::Diterima])->count(),
            'obatTerbaru' => Obat::latest()->take(4)->get(),
            'anggaran' => $anggaran,
            'penyerapan' => $penyerapan,
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
