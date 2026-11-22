<?php

namespace App\Http\Controllers\Perencanaan;

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Actions\Lplpo\VerifyLplpo;
use App\Actions\Permintaan\CreatePermintaanFromLplpo;
use App\Enums\StatusLplpo;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Scopes\FacilityScope;
use App\Services\Lplpo\LplpoItemLookup;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LplpoController extends Controller
{
    public function index(Request $request): View|Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $bulan = $request->integer('bulan') ?: null;
        $tahun = $request->integer('tahun') ?: null;
        $facilityId = $request->integer('facility') ?: null;

        $lplpos = Lplpo::with(['facility'])
            ->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('no_lplpo', 'like', '%'.$search.'%')
                ->orWhereHas('facility', fn ($facility) => $facility->where('nama', 'like', '%'.$search.'%'))))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($bulan, fn ($query) => $query->where('bulan', $bulan))
            ->when($tahun, fn ($query) => $query->where('tahun', $tahun))
            ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderByDesc('versi')
            ->paginate(10)
            ->withQueryString();

        $tahunOptions = Lplpo::withoutGlobalScope(FacilityScope::class)
            ->select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');

        if (! $tahunOptions->contains((int) now()->year)) {
            $tahunOptions->prepend((int) now()->year);
        }

        $bulanOptions = collect(range(1, 12))->mapWithKeys(fn (int $bulan) => [
            $bulan => CarbonImmutable::create(null, $bulan, 1)->locale('id')->monthName,
        ]);

        $payload = [
            'lplpos' => $lplpos,
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
            'tahunOptions' => $tahunOptions->sortDesc()->values(),
            'bulanOptions' => $bulanOptions,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'facility' => $facilityId,
            ],
        ];

        if ($request->boolean('partial')) {
            return response()->view('pages.perencanaan.lplpo.partials.list', $payload);
        }

        return view('pages.perencanaan.lplpo.index', ['title' => 'LPLPO'] + $payload);
    }

    public function create(): View
    {
        return view('pages.perencanaan.lplpo.form', $this->formData('Buat LPLPO'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create-lplpo'), 403);

        $data = $this->validated($request);
        $facilityId = $request->user()->facility_id ?: (int) $data['facility_id'];

        $lplpo = app(GenerateLplpo::class)->handle(
            $facilityId,
            $data['bulan'],
            $data['tahun'],
            $data['items'],
            $request->user()->id,
            $data['catatan'] ?? null,
        );

        return redirect()->route('lplpo.show', $lplpo)
            ->with('status', 'LPLPO disimpan sebagai draft.');
    }

    public function show(Lplpo $lplpo): View
    {
        $lplpo->load(['items.obat', 'facility', 'verifier']);

        return view('pages.perencanaan.lplpo.show', [
            'title' => 'Detail LPLPO '.$lplpo->no_lplpo,
            'lplpo' => $lplpo,
        ]);
    }

    public function edit(Lplpo $lplpo): View
    {
        $this->ensureEditable($lplpo);

        return view('pages.perencanaan.lplpo.form', $this->formData('Ubah LPLPO '.$lplpo->no_lplpo, $lplpo));
    }

    public function update(Request $request, Lplpo $lplpo): RedirectResponse
    {
        abort_unless($request->user()->can('create-lplpo'), 403);
        $this->ensureEditable($lplpo);

        $data = $this->validated($request);
        $facilityId = $request->user()->facility_id ?: (int) $data['facility_id'];

        DB::transaction(function () use ($lplpo, $data, $facilityId) {
            $versi = (int) Lplpo::withoutGlobalScope(FacilityScope::class)
                ->where('facility_id', $facilityId)
                ->where('bulan', $data['bulan'])
                ->where('tahun', $data['tahun'])
                ->whereKeyNot($lplpo->getKey())
                ->max('versi') + 1;

            $lplpo->update([
                'facility_id' => $facilityId,
                'bulan' => $data['bulan'],
                'tahun' => $data['tahun'],
                'versi' => $versi,
                'catatan' => $data['catatan'] ?? null,
                'status' => StatusLplpo::Draft,
                'verified_by' => null,
                'verified_at' => null,
            ]);
        });

        // GenerateLplpo memakai draft dengan periode yang sama sehingga item
        // diperbarui pada dokumen ini (nomor dokumen tetap).
        app(GenerateLplpo::class)->handle(
            $facilityId,
            $data['bulan'],
            $data['tahun'],
            $data['items'],
            $request->user()->id,
            $data['catatan'] ?? null,
        );

        $lplpo->refresh()->update(['catatan' => $data['catatan'] ?? null]);

        return redirect()->route('lplpo.show', $lplpo)
            ->with('status', 'LPLPO diperbarui.');
    }

    public function itemLookup(Request $request, LplpoItemLookup $lookup): JsonResponse
    {
        abort_unless($request->user()->can('create-lplpo'), 403);

        $data = $request->validate([
            'obat_id' => 'required|exists:obat,id',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
            'facility_id' => 'nullable|exists:facilities,id',
        ]);

        $facilityId = $request->user()->facility_id ?: (int) ($data['facility_id'] ?? 0);

        abort_if($facilityId === 0, 422, 'Fasilitas belum dipilih.');

        return response()->json($lookup->handle(
            $facilityId,
            (int) $data['obat_id'],
            (int) $data['bulan'],
            (int) $data['tahun'],
        ));
    }

    public function submit(Request $request, Lplpo $lplpo): RedirectResponse
    {
        abort_unless($request->user()->can('create-lplpo'), 403);

        app(SubmitLplpo::class)->handle($lplpo);

        return redirect()->route('lplpo.show', $lplpo)
            ->with('status', 'LPLPO dikirim untuk verifikasi.');
    }

    public function verify(Request $request, Lplpo $lplpo): RedirectResponse
    {
        abort_unless($request->user()->can('verify-lplpo'), 403);

        $approve = $request->boolean('approve');

        app(VerifyLplpo::class)->handle($lplpo, $approve, $request->user()->id);

        return redirect()->route('lplpo.show', $lplpo)
            ->with('status', $approve ? 'LPLPO diverifikasi.' : 'LPLPO ditolak.');
    }

    public function createPermintaan(Request $request, Lplpo $lplpo): RedirectResponse
    {
        abort_unless($request->user()->can('create-permintaan'), 403);

        app(CreatePermintaanFromLplpo::class)->handle($lplpo, $request->user()->id);

        return redirect()->route('permintaan.index')
            ->with('status', 'Permintaan dibuat dari LPLPO.');
    }

    /** @return array<string, mixed> */
    private function formData(string $title, ?Lplpo $lplpo = null): array
    {
        if ($lplpo) {
            $lplpo->load('items');
        }

        return [
            'title' => $title,
            'lplpo' => $lplpo,
            'obat' => Obat::orderBy('nama')->get(),
            'obatNames' => Obat::orderBy('nama')->pluck('nama', 'id')->all(),
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.pemasukan' => 'nullable|integer|min:0',
            'items.*.pemakaian' => 'nullable|integer|min:0',
            'items.*.stok_akhir' => 'nullable|integer|min:0',
            'items.*.jumlah_diminta' => 'nullable|integer|min:0',
            'items.*.keterangan' => 'nullable|string',
        ]);
    }

    private function ensureEditable(Lplpo $lplpo): void
    {
        abort_unless(
            in_array($lplpo->status, [StatusLplpo::Draft, StatusLplpo::Ditolak], true),
            403,
            'Hanya LPLPO draft atau ditolak yang dapat diubah.',
        );
    }
}
