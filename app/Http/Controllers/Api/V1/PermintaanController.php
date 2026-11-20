<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Permintaan\CreatePermintaanMandiri;
use App\Actions\Permintaan\SubmitPermintaan;
use App\Actions\Permintaan\VerifyPermintaan;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermintaanResource;
use App\Models\PermintaanObat;
use Illuminate\Http\Request;

class PermintaanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view-permintaan'), 403);

        return PermintaanResource::collection(
            PermintaanObat::with('items')->orderByDesc('id')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, CreatePermintaanMandiri $action)
    {
        abort_unless($request->user()->can('create-permintaan'), 403);

        $data = $request->validate([
            'facility_id' => 'nullable|exists:facilities,id',
            'periode_awal' => 'nullable|date',
            'periode_akhir' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.jumlah_diminta' => 'required|integer|min:1',
            'items.*.keterangan' => 'nullable|string',
        ]);

        $facilityId = $request->user()->facility_id ?? $data['facility_id'] ?? null;

        abort_if(! $facilityId, 422, 'facility_id wajib diisi.');

        $permintaan = $action->handle(
            (int) $facilityId,
            $data['items'],
            $data['periode_awal'] ?? null,
            $data['periode_akhir'] ?? null,
            $request->user()->id,
        );

        return (new PermintaanResource($permintaan))->response()->setStatusCode(201);
    }

    public function show(Request $request, PermintaanObat $permintaan)
    {
        abort_unless($request->user()->can('view-permintaan'), 403);

        return new PermintaanResource($permintaan->load('items'));
    }

    public function submit(Request $request, PermintaanObat $permintaan, SubmitPermintaan $action)
    {
        abort_unless($request->user()->can('create-permintaan'), 403);

        return new PermintaanResource($action->handle($permintaan)->load('items'));
    }

    public function verify(Request $request, PermintaanObat $permintaan, VerifyPermintaan $action)
    {
        abort_unless($request->user()->can('verify-permintaan'), 403);

        $data = $request->validate([
            'decisions' => 'array',
            'decisions.*.jumlah_disetujui' => 'required_with:decisions|integer|min:0',
            'decisions.*.status_item' => 'nullable|in:disetujui,sebagian,backorder,ditolak,selesai',
            'decisions.*.keterangan' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        return new PermintaanResource($action->handle(
            $permintaan,
            $data['decisions'] ?? [],
            $request->user()->id,
            $data['catatan'] ?? null,
        )->load('items'));
    }
}
