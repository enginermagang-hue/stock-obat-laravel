<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Opname\CreateAdjustmentFromOpname;
use App\Actions\Opname\GenerateOpname;
use App\Actions\Opname\InputStokFisik;
use App\Actions\Opname\VerifyOpname;
use App\Enums\ModeOpname;
use App\Enums\TipeOpname;
use App\Http\Controllers\Controller;
use App\Http\Resources\StockOpnameResource;
use App\Models\StockOpname;
use Illuminate\Http\Request;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('input-opname'), 403);

        return StockOpnameResource::collection(
            StockOpname::with('items')->orderByDesc('id')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, GenerateOpname $action)
    {
        abort_unless($request->user()->can('input-opname'), 403);

        $data = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
            'tipe' => 'required|in:berkala,mendadak,lokal',
            'mode' => 'required|in:terbuka,buta',
            'catatan' => 'nullable|string',
        ]);

        $opname = $action->handle(
            $data['bulan'],
            $data['tahun'],
            TipeOpname::from($data['tipe']),
            ModeOpname::from($data['mode']),
            null,
            $request->user()->id,
            $data['catatan'] ?? null,
        );

        return (new StockOpnameResource($opname->load('items')))->response()->setStatusCode(201);
    }

    public function inputFisik(Request $request, StockOpname $opname, InputStokFisik $action)
    {
        abort_unless($request->user()->can('input-opname'), 403);

        $data = $request->validate([
            'fisik' => 'required|array',
        ]);

        $updated = $action->handle($opname, $data['fisik']);

        return new StockOpnameResource($updated->load('items'));
    }

    public function verify(Request $request, StockOpname $opname, VerifyOpname $action)
    {
        abort_unless($request->user()->can('verify-opname'), 403);

        return new StockOpnameResource($action->handle($opname, $request->user()->id));
    }

    public function approve(Request $request, StockOpname $opname, CreateAdjustmentFromOpname $action)
    {
        abort_unless($request->user()->can('verify-opname'), 403);

        $adjustment = $action->handle($opname, $request->user()->id);

        return response()->json(['data' => [
            'opname_id' => $opname->id,
            'adjustment_id' => $adjustment->id,
            'no_penyesuaian' => $adjustment->no_penyesuaian,
        ]]);
    }
}
