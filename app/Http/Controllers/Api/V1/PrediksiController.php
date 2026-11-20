<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Prediksi\EvaluasiAkurasi;
use App\Actions\Prediksi\PrediksiKebutuhanObat;
use App\Http\Controllers\Controller;
use App\Http\Resources\PrediksiResource;
use App\Models\PrediksiObat;
use Illuminate\Http\Request;

class PrediksiController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-prediksi'), 403);

        return PrediksiResource::collection(
            PrediksiObat::orderByDesc('periode_tahun')->orderByDesc('periode_bulan')->paginate($request->integer('per_page', 50))
        );
    }

    public function store(Request $request, PrediksiKebutuhanObat $action)
    {
        abort_unless($request->user()->can('manage-prediksi'), 403);

        $data = $request->validate([
            'periode_bulan' => 'required|integer|between:1,12',
            'periode_tahun' => 'required|integer|min:2000',
            'facility_id' => 'nullable|exists:facilities,id',
        ]);

        $facilityId = $request->user()->facility_id ?? ($data['facility_id'] ?? null);

        $results = $action->handle(
            $data['periode_bulan'],
            $data['periode_tahun'],
            $facilityId,
            $request->user()->id,
        );

        return PrediksiResource::collection($results)->response()->setStatusCode(201);
    }

    public function show(Request $request, PrediksiObat $prediksi)
    {
        abort_unless($request->user()->can('manage-prediksi'), 403);

        return new PrediksiResource($prediksi);
    }

    public function validatePrediction(Request $request, PrediksiObat $prediksi, EvaluasiAkurasi $action)
    {
        abort_unless($request->user()->can('manage-prediksi'), 403);

        return new PrediksiResource($action->handle($prediksi));
    }

    public function akurasi(Request $request, EvaluasiAkurasi $action)
    {
        abort_unless($request->user()->can('manage-prediksi'), 403);

        return response()->json(['data' => $action->summary($request->integer('tahun') ?: null)]);
    }
}
