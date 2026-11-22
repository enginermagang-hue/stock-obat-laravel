<?php

namespace App\Http\Controllers\Perencanaan;

use App\Actions\Permintaan\CreatePermintaanMandiri;
use App\Actions\Permintaan\SubmitPermintaan;
use App\Actions\Permintaan\VerifyPermintaan;
use App\Enums\StatusPermintaan;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\PermintaanObat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermintaanController extends Controller
{
    public function index(Request $request): View|Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $sumber = (string) $request->query('sumber', '');
        $facilityId = $request->integer('facility') ?: null;
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $permintaans = PermintaanObat::with('facility')
            ->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('no_permintaan', 'like', '%'.$search.'%')
                ->orWhereHas('facility', fn ($facility) => $facility->where('nama', 'like', '%'.$search.'%'))))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($sumber !== '', fn ($query) => $query->where('sumber', $sumber))
            ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
            // Rentang periode bersifat tumpang tindih: dokumen yang periodenya
            // beririsan dengan filter tetap tampil.
            ->when($from !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->whereNull('periode_akhir')
                ->orWhereDate('periode_akhir', '>=', $from)))
            ->when($to !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->whereNull('periode_awal')
                ->orWhereDate('periode_awal', '<=', $to)))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $payload = [
            'permintaans' => $permintaans,
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sumber' => $sumber,
                'facility' => $facilityId,
                'from' => $from,
                'to' => $to,
            ],
        ];

        if ($request->boolean('partial')) {
            return response()->view('pages.perencanaan.permintaan.partials.list', $payload);
        }

        return view('pages.perencanaan.permintaan.index', ['title' => 'Permintaan Obat'] + $payload);
    }

    public function create(): View
    {
        return view('pages.perencanaan.permintaan.form', $this->formData('Permintaan Mandiri'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create-permintaan'), 403);

        $data = $this->validated($request);
        $facilityId = $request->user()->facility_id ?: (int) $data['facility_id'];

        $permintaan = app(CreatePermintaanMandiri::class)->handle(
            $facilityId,
            $data['items'],
            $data['periode_awal'] ?? null,
            $data['periode_akhir'] ?? null,
            $request->user()->id,
        );

        return redirect()->route('permintaan.show', $permintaan)
            ->with('status', 'Permintaan draft dibuat.');
    }

    public function show(PermintaanObat $permintaan): View
    {
        $permintaan->load(['items.obat', 'facility', 'verifier', 'lplpo']);

        return view('pages.perencanaan.permintaan.show', [
            'title' => 'Detail Permintaan '.$permintaan->no_permintaan,
            'permintaan' => $permintaan,
        ]);
    }

    public function edit(PermintaanObat $permintaan): View
    {
        $this->ensureEditable($permintaan);

        return view('pages.perencanaan.permintaan.form', $this->formData('Ubah Permintaan '.$permintaan->no_permintaan, $permintaan));
    }

    public function update(Request $request, PermintaanObat $permintaan): RedirectResponse
    {
        abort_unless($request->user()->can('create-permintaan'), 403);
        $this->ensureEditable($permintaan);

        $data = $this->validated($request);
        $facilityId = $request->user()->facility_id ?: (int) $data['facility_id'];

        DB::transaction(function () use ($permintaan, $data, $facilityId) {
            $permintaan->update([
                'facility_id' => $facilityId,
                'periode_awal' => $data['periode_awal'] ?? null,
                'periode_akhir' => $data['periode_akhir'] ?? null,
            ]);

            $permintaan->items()->delete();

            foreach ($data['items'] as $item) {
                $permintaan->items()->create([
                    'obat_id' => $item['obat_id'],
                    'jumlah_diminta' => (int) $item['jumlah_diminta'],
                    'keterangan' => $item['keterangan'] ?? null,
                ]);
            }
        });

        return redirect()->route('permintaan.show', $permintaan)
            ->with('status', 'Permintaan diperbarui.');
    }

    public function submit(Request $request, PermintaanObat $permintaan): RedirectResponse
    {
        abort_unless($request->user()->can('create-permintaan'), 403);

        app(SubmitPermintaan::class)->handle($permintaan);

        return redirect()->route('permintaan.show', $permintaan)
            ->with('status', 'Permintaan dikirim ke apoteker.');
    }

    public function verify(Request $request, PermintaanObat $permintaan): RedirectResponse
    {
        abort_unless($request->user()->can('verify-permintaan'), 403);

        $data = $request->validate([
            'decisions' => 'required|array',
            'decisions.*.jumlah_disetujui' => 'nullable|integer|min:0',
            'decisions.*.status_item' => 'nullable|string',
            'decisions.*.keterangan' => 'nullable|string',
            'catatan_verifikasi' => 'nullable|string',
        ]);

        app(VerifyPermintaan::class)->handle(
            $permintaan,
            $data['decisions'],
            $request->user()->id,
            $data['catatan_verifikasi'] ?? null,
        );

        return redirect()->route('permintaan.show', $permintaan)
            ->with('status', 'Permintaan diverifikasi.');
    }

    /** @return array<string, mixed> */
    private function formData(string $title, ?PermintaanObat $permintaan = null): array
    {
        if ($permintaan) {
            $permintaan->load('items');
        }

        return [
            'title' => $title,
            'permintaan' => $permintaan,
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
            'periode_awal' => 'nullable|date',
            'periode_akhir' => 'nullable|date|after_or_equal:periode_awal',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id|distinct',
            'items.*.jumlah_diminta' => 'required|integer|min:1',
            'items.*.keterangan' => 'nullable|string',
        ]);
    }

    private function ensureEditable(PermintaanObat $permintaan): void
    {
        abort_unless(
            $permintaan->status === StatusPermintaan::Draft,
            403,
            'Hanya permintaan draft yang dapat diubah.',
        );
    }
}
