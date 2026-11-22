<?php

namespace App\Http\Controllers\Distribusi;

use App\Actions\Distribusi\ApproveDistribution;
use App\Actions\Distribusi\CancelDistribution;
use App\Actions\Distribusi\ConfirmDistribution;
use App\Actions\Distribusi\DistributeDrugs;
use App\Actions\Distribusi\PreviewDistribution;
use App\Actions\Permintaan\FulfillPermintaan;
use App\Enums\StatusDistribusi;
use App\Enums\StatusPermintaan;
use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Facility;
use App\Models\PermintaanObat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DistributionController extends Controller
{
    public function index(Request $request): View|Response
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');
        $facilityFilter = $request->integer('facility') ?: null;
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $distributions = Distribution::query()
            ->with(['facility', 'permintaan'])
            ->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('no_surat', 'like', '%'.$search.'%')
                ->orWhereHas('facility', fn ($facility) => $facility->where('nama', 'like', '%'.$search.'%'))
                ->orWhereHas('permintaan', fn ($permintaan) => $permintaan->where('no_permintaan', 'like', '%'.$search.'%'))))
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->when($facilityFilter, fn ($query) => $query->where('facility_id', $facilityFilter))
            ->when($from !== '', fn ($query) => $query->whereDate('tanggal', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('tanggal', '<=', $to))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $payload = [
            'distributions' => $distributions,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'facility' => $facilityFilter,
                'from' => $from,
                'to' => $to,
            ],
        ];

        if ($request->boolean('partial')) {
            return response()->view('pages.distribusi.partials.list', $payload);
        }

        return view('pages.distribusi.index', [
            'title' => 'Distribusi',
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
        ] + $payload);
    }

    public function create(): View
    {
        return view('pages.distribusi.form', $this->formData('Buat Surat Jalan'));
    }

    public function preview(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('create-distribution'), 403);

        $data = $request->validate([
            'permintaan_id' => 'required|exists:permintaan_obat,id',
        ]);

        $permintaan = PermintaanObat::with(['facility', 'items.obat'])->findOrFail($data['permintaan_id']);

        if (! in_array($permintaan->status, [StatusPermintaan::Disetujui, StatusPermintaan::Sebagian, StatusPermintaan::Diproses], true)) {
            return response()->json([
                'message' => 'Permintaan belum disetujui sehingga belum dapat didistribusikan.',
            ], 422);
        }

        return response()->json(app(PreviewDistribution::class)->handle($permintaan));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create-distribution'), 403);

        $data = $request->validate([
            'permintaan_id' => 'required|exists:permintaan_obat,id',
            'tanggal' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        $permintaan = PermintaanObat::findOrFail($data['permintaan_id']);

        $distribution = app(FulfillPermintaan::class)->handle(
            $permintaan,
            null,
            $request->user()->id,
            $data['tanggal'] ?? null,
            $data['catatan'] ?? null,
        );

        return redirect()->route('distribusi.show', $distribution)
            ->with('status', 'Surat jalan draft dibuat.');
    }

    public function show(Distribution $distribution): View
    {
        $distribution->load([
            'facility',
            'permintaan',
            'items.obat',
            'items.batch',
            'items.permintaanItem',
            'pengirim',
            'approver',
            'user',
        ]);

        return view('pages.distribusi.show', [
            'title' => 'Detail Surat Jalan '.$distribution->no_surat,
            'distribution' => $distribution,
        ]);
    }

    public function edit(Distribution $distribution): View
    {
        $this->ensureEditable($distribution);

        return view('pages.distribusi.form', $this->formData('Ubah Surat Jalan '.$distribution->no_surat, $distribution));
    }

    public function update(Request $request, Distribution $distribution): RedirectResponse
    {
        abort_unless($request->user()->can('create-distribution'), 403);
        $this->ensureEditable($distribution);

        $data = $request->validate([
            'tanggal' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        $distribution->update([
            'tanggal' => $data['tanggal'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ]);

        return redirect()->route('distribusi.show', $distribution)
            ->with('status', 'Surat jalan diperbarui.');
    }

    public function approve(Request $request, Distribution $distribution): RedirectResponse
    {
        abort_unless($request->user()->can('approve-distribution'), 403);

        app(ApproveDistribution::class)->handle($distribution, $request->user()->id);

        return redirect()->route('distribusi.show', $distribution)
            ->with('status', 'Surat jalan disetujui.');
    }

    public function send(Request $request, Distribution $distribution): RedirectResponse
    {
        abort_unless($request->user()->can('create-distribution'), 403);

        app(DistributeDrugs::class)->handle($distribution, $request->user()->id);

        return redirect()->route('distribusi.show', $distribution)
            ->with('status', 'Surat jalan dikirim dan stok keluar dicatat.');
    }

    public function confirm(Request $request, Distribution $distribution): RedirectResponse
    {
        abort_unless($request->user()->can('confirm-distribution'), 403);

        $data = $request->validate([
            'penerima_nama' => 'required|string|max:255',
            'penerima_nip' => 'nullable|string|max:30',
            'penerima_jabatan' => 'nullable|string|max:120',
            'penerima_unit' => 'nullable|string|max:160',
            'file_tanda_terima' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->hasFile('file_tanda_terima')
            ? $request->file('file_tanda_terima')->store('tanda-terima', 'local')
            : null;

        app(ConfirmDistribution::class)->handle(
            $distribution,
            $data['penerima_nama'],
            $request->user()->id,
            $path,
            $data['penerima_nip'] ?? null,
            $data['penerima_jabatan'] ?? null,
            $data['penerima_unit'] ?? null,
        );

        return redirect()->route('distribusi.show', $distribution)
            ->with('status', 'Penerimaan dikonfirmasi.');
    }

    public function tandaTerima(Request $request, Distribution $distribution): BinaryFileResponse
    {
        abort_unless($request->user()->can('view-distribution'), 403);
        abort_if($distribution->file_tanda_terima === null, 404);

        $path = Storage::disk('local')->path($distribution->file_tanda_terima);
        abort_unless(file_exists($path), 404);

        return response()->file($path);
    }

    public function cancel(Request $request, Distribution $distribution): RedirectResponse
    {
        abort_unless($request->user()->can('approve-distribution'), 403);

        app(CancelDistribution::class)->handle($distribution);

        return redirect()->route('distribusi.show', $distribution)
            ->with('status', 'Surat jalan dibatalkan.');
    }

    /** @return array<string, mixed> */
    private function formData(string $title, ?Distribution $distribution = null): array
    {
        return [
            'title' => $title,
            'distribution' => $distribution,
            'permintaans' => PermintaanObat::query()
                ->with('facility')
                ->whereIn('status', [StatusPermintaan::Disetujui, StatusPermintaan::Sebagian, StatusPermintaan::Diproses])
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        ];
    }

    private function ensureEditable(Distribution $distribution): void
    {
        abort_unless(
            $distribution->status === StatusDistribusi::Draft,
            403,
            'Hanya surat jalan draft yang dapat diubah.',
        );
    }
}
