<?php

namespace App\Http\Controllers\Pembelian;

use App\Actions\Anggaran\ValidatePagu;
use App\Actions\Pembelian\ApprovePurchaseOrder;
use App\Enums\StatusPurchaseOrder;
use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\PurchaseOrder;
use App\Models\SumberDana;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View|Response
    {
        $search = trim((string) $request->query('search', ''));

        $orders = PurchaseOrder::with(['supplier', 'sumberDana'])
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('no_po', 'like', '%'.$search.'%')
                ->orWhereHas('supplier', fn ($supplier) => $supplier->where('nama', 'like', '%'.$search.'%'))))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->boolean('partial')) {
            return response()->view('pages.pembelian.purchase-order.partials.list', ['orders' => $orders]);
        }

        return view('pages.pembelian.purchase-order.index', [
            'title' => 'Purchase Order',
            'orders' => $orders,
        ]);
    }

    public function create(): View
    {
        return view('pages.pembelian.purchase-order.form', $this->formData('Buat PO'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('receive-goods'), 403);

        $data = $this->validated($request);

        $po = DB::transaction(function () use ($data, $request) {
            $po = PurchaseOrder::create([
                'no_po' => $this->nextNumber(),
                'supplier_id' => $data['supplier_id'],
                'sumber_dana_id' => $data['sumber_dana_id'],
                'tanggal_po' => $data['tanggal_po'],
                'status' => StatusPurchaseOrder::Draft,
                'total' => 0,
                'user_id' => $request->user()->id,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $this->syncItems($po, $data['items']);

            return $po;
        });

        return redirect()->route('pembelian.purchase-order.show', $po)
            ->with('status', 'Purchase Order dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'sumberDana', 'approver', 'items.obat', 'receipts.items']);

        return view('pages.pembelian.purchase-order.show', [
            'title' => 'Detail PO '.$purchaseOrder->no_po,
            'po' => $purchaseOrder,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->ensureEditable($purchaseOrder);
        $purchaseOrder->load('items');

        return view('pages.pembelian.purchase-order.form', $this->formData('Ubah PO '.$purchaseOrder->no_po, $purchaseOrder));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->can('receive-goods'), 403);
        $this->ensureEditable($purchaseOrder);

        $data = $this->validated($request);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'sumber_dana_id' => $data['sumber_dana_id'],
                'tanggal_po' => $data['tanggal_po'],
                'catatan' => $data['catatan'] ?? null,
                'status' => StatusPurchaseOrder::Draft,
                'approved_by' => null,
                'alasan_override_pagu' => null,
            ]);

            $purchaseOrder->items()->delete();
            $this->syncItems($purchaseOrder, $data['items']);
        });

        return redirect()->route('pembelian.purchase-order.show', $purchaseOrder)
            ->with('status', 'Purchase Order diperbarui.');
    }

    public function send(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->can('receive-goods'), 403);

        if ($purchaseOrder->status !== StatusPurchaseOrder::Draft) {
            return back();
        }

        if (! $purchaseOrder->approved_by) {
            return back()->withErrors(['status' => 'PO harus disetujui apoteker sebelum dikirim.']);
        }

        $purchaseOrder->update(['status' => StatusPurchaseOrder::Dikirim]);

        return redirect()->route('pembelian.purchase-order.show', $purchaseOrder)
            ->with('status', 'PO dikirim.');
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->can('approve-po'), 403);

        $request->validate(['alasan_override_pagu' => 'nullable|string']);

        try {
            app(ApprovePurchaseOrder::class)->handle(
                $purchaseOrder,
                $request->user()->id,
                $request->input('alasan_override_pagu') ?: null,
            );
        } catch (ValidationException $exception) {
            $warning = app(ValidatePagu::class)->handle(
                (int) $purchaseOrder->tanggal_po->year,
                $purchaseOrder->sumber_dana_id,
                0,
                (float) $purchaseOrder->total,
                $purchaseOrder->id,
            );

            return back()
                ->withErrors(['alasan_override_pagu' => $exception->validator->errors()->first()])
                ->with('pagu_warning', [
                    'po_id' => $purchaseOrder->id,
                    'data' => collect($warning)->only(['pagu', 'komitmen', 'realisasi', 'sisa', 'diminta'])->all(),
                ])
                ->withInput();
        }

        return redirect()->route('pembelian.purchase-order.show', $purchaseOrder)
            ->with('status', 'PO disetujui dan siap dikirim.');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->can('approve-po'), 403);

        if ($purchaseOrder->status !== StatusPurchaseOrder::Draft) {
            return back()->withErrors(['status' => 'Hanya PO berstatus draft yang dapat ditolak.']);
        }

        $purchaseOrder->update([
            'status' => StatusPurchaseOrder::Ditolak,
            'approved_by' => null,
        ]);

        activity('pembelian')
            ->causedBy($request->user())
            ->performedOn($purchaseOrder)
            ->log('Purchase order ditolak');

        return redirect()->route('pembelian.purchase-order.show', $purchaseOrder)
            ->with('status', 'PO ditolak dan dapat diperbaiki.');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->can('approve-po'), 403);

        $purchaseOrder->update(['status' => StatusPurchaseOrder::Dibatalkan]);

        return redirect()->route('pembelian.purchase-order.show', $purchaseOrder)
            ->with('status', 'PO dibatalkan.');
    }

    /** @return array<string, mixed> */
    private function formData(string $title, ?PurchaseOrder $po = null): array
    {
        return [
            'title' => $title,
            'po' => $po,
            'suppliers' => Supplier::orderBy('nama')->get(),
            'sumberDana' => SumberDana::orderBy('nama')->get(),
            'obat' => Obat::orderBy('nama')->get(),
            'obatMeta' => Obat::query()
                ->get(['id', 'nama', 'bentuk_sediaan', 'satuan', 'harga_satuan_terakhir'])
                ->mapWithKeys(fn (Obat $obat) => [
                    $obat->id => [
                        'nama' => $obat->nama,
                        'bentuk' => $obat->bentuk_sediaan->value,
                        'satuan' => $obat->satuan,
                        'harga' => (float) $obat->harga_satuan_terakhir,
                    ],
                ])
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => 'required|exists:supplier,id',
            'sumber_dana_id' => 'required|exists:sumber_dana,id',
            'tanggal_po' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:obat,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
        ]);
    }

    /** @param array<int, array{obat_id:int,jumlah:int,harga_beli:numeric}> $items */
    private function syncItems(PurchaseOrder $po, array $items): void
    {
        $total = 0;

        foreach ($items as $item) {
            $subtotal = $item['jumlah'] * $item['harga_beli'];
            $po->items()->create([
                'obat_id' => $item['obat_id'],
                'jumlah' => $item['jumlah'],
                'harga_beli' => $item['harga_beli'],
                'subtotal' => $subtotal,
            ]);
            $total += $subtotal;
        }

        $po->update(['total' => $total]);
    }

    private function ensureEditable(PurchaseOrder $po): void
    {
        abort_unless(
            in_array($po->status, [StatusPurchaseOrder::Draft, StatusPurchaseOrder::Ditolak], true),
            403,
            'Hanya PO draft atau ditolak yang dapat diubah.',
        );
    }

    private function nextNumber(): string
    {
        $prefix = 'PO-'.now()->format('Y').'-';
        $last = PurchaseOrder::where('no_po', 'like', $prefix.'%')->orderByDesc('id')->value('no_po');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
