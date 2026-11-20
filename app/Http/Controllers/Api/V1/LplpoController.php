<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Actions\Lplpo\VerifyLplpo;
use App\Actions\Permintaan\CreatePermintaanFromLplpo;
use App\Http\Controllers\Controller;
use App\Http\Resources\LplpoResource;
use App\Http\Resources\PermintaanResource;
use App\Models\Lplpo;
use Illuminate\Http\Request;

class LplpoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view-lplpo'), 403);

        return LplpoResource::collection(
            Lplpo::with('items')->orderByDesc('tahun')->orderByDesc('bulan')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, GenerateLplpo $action)
    {
        abort_unless($request->user()->can('create-lplpo'), 403);

        $data = $request->validate([
            'facility_id' => 'nullable|exists:facilities,id',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.pemasukan' => 'nullable|integer|min:0',
            'items.*.pemakaian' => 'nullable|integer|min:0',
            'items.*.stok_akhir' => 'nullable|integer|min:0',
            'items.*.jumlah_diminta' => 'nullable|integer|min:0',
        ]);

        $facilityId = $request->user()->facility_id ?? $data['facility_id'] ?? null;

        abort_if(! $facilityId, 422, 'facility_id wajib diisi.');

        $lplpo = $action->handle(
            (int) $facilityId,
            $data['bulan'],
            $data['tahun'],
            $data['items'],
            $request->user()->id,
            $data['catatan'] ?? null,
        );

        return (new LplpoResource($lplpo))->response()->setStatusCode(201);
    }

    public function show(Request $request, Lplpo $lplpo)
    {
        abort_unless($request->user()->can('view-lplpo'), 403);

        return new LplpoResource($lplpo->load('items'));
    }

    public function submit(Request $request, Lplpo $lplpo, SubmitLplpo $action)
    {
        abort_unless($request->user()->can('create-lplpo'), 403);

        return new LplpoResource($action->handle($lplpo)->load('items'));
    }

    public function verify(Request $request, Lplpo $lplpo, VerifyLplpo $action)
    {
        abort_unless($request->user()->can('verify-lplpo'), 403);

        $data = $request->validate([
            'approve' => 'required|boolean',
            'catatan' => 'nullable|string',
        ]);

        return new LplpoResource($action->handle(
            $lplpo,
            (bool) $data['approve'],
            $request->user()->id,
            $data['catatan'] ?? null,
        )->load('items'));
    }

    public function createPermintaan(Request $request, Lplpo $lplpo, CreatePermintaanFromLplpo $action)
    {
        abort_unless($request->user()->can('create-permintaan'), 403);

        return (new PermintaanResource($action->handle($lplpo, $request->user()->id)))
            ->response()->setStatusCode(201);
    }
}
