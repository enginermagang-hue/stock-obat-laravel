<?php

namespace App\Livewire;

use App\Enums\StatusLplpo;
use App\Enums\StatusPermintaan;
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
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
