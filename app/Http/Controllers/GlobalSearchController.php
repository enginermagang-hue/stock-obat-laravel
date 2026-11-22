<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Obat;
use App\Models\SumberDana;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $groups = [];

        if (strlen($term) >= 2) {
            $like = '%'.$term.'%';

            $groups = array_filter([
                'Obat' => Obat::query()
                    ->where(fn ($q) => $q->where('nama', 'like', $like)->orWhere('kode_obat', 'like', $like))
                    ->limit(5)->get(['id', 'nama', 'kode_obat']),
                'Supplier' => Supplier::query()
                    ->where(fn ($q) => $q->where('nama', 'like', $like)->orWhere('kode', 'like', $like))
                    ->limit(5)->get(['id', 'nama', 'kode']),
                'Puskesmas' => Facility::query()
                    ->where(fn ($q) => $q->where('nama', 'like', $like)->orWhere('kode', 'like', $like))
                    ->limit(5)->get(['id', 'nama', 'kode']),
                'Sumber Dana' => SumberDana::query()
                    ->where(fn ($q) => $q->where('nama', 'like', $like)->orWhere('kode', 'like', $like))
                    ->limit(5)->get(['id', 'nama', 'kode']),
            ], fn ($items) => $items->isNotEmpty());
        }

        return response()->json(['groups' => $groups]);
    }
}
