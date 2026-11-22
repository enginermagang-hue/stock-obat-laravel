<?php

namespace App\Http\Controllers\Stok;

use App\Actions\Stock\ProcessStockAdjustment;
use App\Enums\StatusPenyesuaian;
use App\Enums\TipePenyesuaian;
use App\Http\Controllers\Controller;
use App\Models\BatchObat;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdjustmentController extends Controller
{
    public function index(): View
    {
        $adjustments = StockAdjustment::withCount('items')->orderByDesc('id')->paginate(10);

        return view('pages.stok.adjustment', [
            'title' => 'Penyesuaian Stok',
            'adjustments' => $adjustments,
            'batches' => BatchObat::with('obat')->where('jumlah_sisa', '>', 0)->orderBy('tanggal_kedaluwarsa')->get(),
            'tipeOptions' => TipePenyesuaian::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-stock'), 403);

        $data = $request->validate([
            'tipe' => 'required|in:koreksi,expired,rusak,hilang,retur',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:batch_obat,id',
            'items.*.jumlah' => 'required|integer|not_in:0',
            'items.*.alasan' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $request) {
            $adjustment = StockAdjustment::create([
                'no_penyesuaian' => $this->nextNumber(),
                'tipe' => $data['tipe'],
                'tanggal' => $data['tanggal'],
                'status' => StatusPenyesuaian::Draft,
                'user_id' => $request->user()->id,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $batch = BatchObat::findOrFail($item['batch_id']);
                $adjustment->items()->create([
                    'batch_id' => $batch->id,
                    'obat_id' => $batch->obat_id,
                    'jumlah' => (int) $item['jumlah'],
                    'alasan' => $item['alasan'] ?? null,
                    'nilai' => (int) $item['jumlah'] * (float) $batch->harga_beli,
                ]);
            }
        });

        return redirect()->route('stok.adjustment')->with('status', 'Penyesuaian dibuat.');
    }

    public function process(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        abort_unless($request->user()->can('approve-po'), 403);

        app(ProcessStockAdjustment::class)->handle($adjustment, $request->user()->id);

        return redirect()->route('stok.adjustment')->with('status', 'Penyesuaian diproses ke ledger.');
    }

    public function cancel(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        abort_unless($request->user()->can('approve-po'), 403);

        $adjustment->update(['status' => StatusPenyesuaian::Dibatalkan]);

        return redirect()->route('stok.adjustment')->with('status', 'Penyesuaian dibatalkan.');
    }

    private function nextNumber(): string
    {
        $prefix = 'ADJ-'.now()->format('Y').'-';
        $last = StockAdjustment::where('no_penyesuaian', 'like', $prefix.'%')->orderByDesc('id')->value('no_penyesuaian');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
