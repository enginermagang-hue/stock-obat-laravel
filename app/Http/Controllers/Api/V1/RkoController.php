<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Rko\ApproveRko;
use App\Actions\Rko\BuildRko;
use App\Actions\Rko\GeneratePoFromRko;
use App\Http\Controllers\Controller;
use App\Http\Resources\RkoResource;
use App\Models\Rko;
use Illuminate\Http\Request;

class RkoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-rko'), 403);

        return RkoResource::collection(
            Rko::with('items')->orderByDesc('tahun')->orderByDesc('periode')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, BuildRko $action)
    {
        abort_unless($request->user()->can('manage-rko'), 403);

        $data = $request->validate([
            'tahun' => 'required|integer|min:2000',
            'periode' => 'required|integer|between:1,12',
            'lead_time_bulan' => 'nullable|integer|min:1|max:12',
            'faktor_pengaman' => 'nullable|numeric|min:0.5|max:5',
            'sumber_dana_id' => 'nullable|exists:sumber_dana,id',
            'supplier_id' => 'nullable|exists:supplier,id',
            'catatan' => 'nullable|string',
        ]);

        $rko = $action->handle($data['tahun'], $data['periode'], $data, $request->user()->id);

        return (new RkoResource($rko->load('items')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Rko $rko)
    {
        abort_unless($request->user()->can('manage-rko'), 403);

        return new RkoResource($rko->load('items'));
    }

    public function approve(Request $request, Rko $rko, ApproveRko $action)
    {
        abort_unless($request->user()->can('manage-rko'), 403);

        return new RkoResource($action->handle($rko, $request->user()->id)->load('items'));
    }

    public function purchaseOrder(Request $request, Rko $rko, GeneratePoFromRko $action)
    {
        abort_unless($request->user()->can('approve-po'), 403);

        $orders = $action->handle($rko, $request->user()->id);

        return response()->json(['data' => $orders->map(fn ($po) => [
            'id' => $po->id,
            'no_po' => $po->no_po,
            'status' => $po->status->value,
            'total' => (float) $po->total,
        ])], 201);
    }
}
