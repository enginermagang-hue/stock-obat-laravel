<?php

namespace App\Livewire;

use App\Models\Facility;
use App\Models\Obat;
use App\Models\SumberDana;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'totalObat' => Obat::count(),
            'totalPuskesmas' => Facility::where('jenis', 'puskesmas')->count(),
            'totalSumberDana' => SumberDana::count(),
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
