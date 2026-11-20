<?php

namespace App\Livewire;

use App\Models\Facility;
use App\Models\Obat;
use App\Models\SumberDana;
use App\Models\Supplier;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public function render()
    {
        $results = [];
        $term = trim($this->query);

        if (strlen($term) >= 2) {
            $like = '%'.$term.'%';

            $results = [
                'Obat' => Obat::where('nama', 'like', $like)
                    ->orWhere('kode_obat', 'like', $like)
                    ->limit(5)->get(),
                'Supplier' => Supplier::where('nama', 'like', $like)
                    ->orWhere('kode', 'like', $like)
                    ->limit(5)->get(),
                'Puskesmas' => Facility::where('nama', 'like', $like)
                    ->orWhere('kode', 'like', $like)
                    ->limit(5)->get(),
                'Sumber Dana' => SumberDana::where('nama', 'like', $like)
                    ->orWhere('kode', 'like', $like)
                    ->limit(5)->get(),
            ];

            $results = array_filter($results, fn ($items) => $items->isNotEmpty());
        }

        return view('livewire.global-search', ['results' => $results]);
    }
}
