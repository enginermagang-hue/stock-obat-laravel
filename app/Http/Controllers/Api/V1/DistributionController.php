<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Distribusi\ApproveDistribution;
use App\Actions\Distribusi\CancelDistribution;
use App\Actions\Distribusi\ConfirmDistribution;
use App\Actions\Distribusi\CreateDistribution;
use App\Actions\Distribusi\DistributeDrugs;
use App\Actions\Permintaan\FulfillPermintaan;
use App\Http\Controllers\Controller;
use App\Http\Resources\DistributionResource;
use App\Models\Distribution;
use App\Models\PermintaanObat;
use Illuminate\Http\Request;

class DistributionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view-distribution'), 403);

        $query = Distribution::query()
            ->with(['facility', 'items.obat', 'items.batch'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('facility_id')) {
            $query->where('facility_id', $request->integer('facility_id'));
        }

        return DistributionResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function store(
        Request $request,
        CreateDistribution $createDistribution,
        FulfillPermintaan $fulfillPermintaan,
    ) {
        abort_unless($request->user()->can('create-distribution'), 403);

        $data = $request->validate([
            'facility_id' => 'nullable|exists:facilities,id',
            'permintaan_id' => 'nullable|exists:permintaan_obat,id',
            'tanggal' => 'nullable|date',
            'catatan' => 'nullable|string',
            'items' => 'nullable|array|min:1',
            'items.*.obat_id' => 'required_with:items|exists:obat,id',
            'items.*.jumlah' => 'required_with:items|integer|min:1',
            'items.*.permintaan_item_id' => 'nullable|exists:permintaan_obat_item,id',
            'items.*.keterangan' => 'nullable|string',
        ]);

        abort_if(empty($data['items']) && empty($data['permintaan_id']), 422, 'items atau permintaan_id wajib diisi.');

        if (empty($data['items'])) {
            $permintaan = PermintaanObat::findOrFail($data['permintaan_id']);

            $distribution = $fulfillPermintaan->handle(
                $permintaan,
                null,
                $request->user()->id,
                $data['tanggal'] ?? null,
                $data['catatan'] ?? null,
            );
        } else {
            $facilityId = $request->user()->facility_id ?? $data['facility_id'] ?? null;

            abort_if(! $facilityId, 422, 'facility_id wajib diisi.');

            $distribution = $createDistribution->handle(
                (int) $facilityId,
                $data['items'],
                isset($data['permintaan_id']) ? (int) $data['permintaan_id'] : null,
                $request->user()->id,
                $data['tanggal'] ?? null,
                $data['catatan'] ?? null,
            );
        }

        return (new DistributionResource(
            $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch'])
        ))->response()->setStatusCode(201);
    }

    public function show(Request $request, Distribution $distribution)
    {
        abort_unless($request->user()->can('view-distribution'), 403);

        return new DistributionResource(
            $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch'])
        );
    }

    public function approve(Request $request, Distribution $distribution, ApproveDistribution $action)
    {
        abort_unless($request->user()->can('approve-distribution'), 403);

        return new DistributionResource(
            $action->handle($distribution, $request->user()->id)
                ->load(['facility', 'permintaan', 'items.obat', 'items.batch'])
        );
    }

    public function send(Request $request, Distribution $distribution, DistributeDrugs $action)
    {
        abort_unless($request->user()->can('create-distribution'), 403);

        return new DistributionResource(
            $action->handle($distribution, $request->user()->id)
                ->load(['facility', 'permintaan', 'items.obat', 'items.batch'])
        );
    }

    public function confirm(Request $request, Distribution $distribution, ConfirmDistribution $action)
    {
        abort_unless($request->user()->can('confirm-distribution'), 403);

        $data = $request->validate([
            'penerima_nama' => 'nullable|string|max:255',
            'penerima_nip' => 'nullable|string|max:30',
            'penerima_jabatan' => 'nullable|string|max:120',
            'penerima_unit' => 'nullable|string|max:160',
        ]);

        return new DistributionResource(
            $action->handle(
                $distribution,
                $data['penerima_nama'] ?? null,
                $request->user()->id,
                null,
                $data['penerima_nip'] ?? null,
                $data['penerima_jabatan'] ?? null,
                $data['penerima_unit'] ?? null,
            )->load(['facility', 'permintaan', 'items.obat', 'items.batch'])
        );
    }

    public function cancel(Request $request, Distribution $distribution, CancelDistribution $action)
    {
        abort_unless($request->user()->can('approve-distribution'), 403);

        $data = $request->validate([
            'alasan' => 'nullable|string',
        ]);

        return new DistributionResource(
            $action->handle($distribution, $data['alasan'] ?? null)
                ->load(['facility', 'permintaan', 'items.obat', 'items.batch'])
        );
    }
}
