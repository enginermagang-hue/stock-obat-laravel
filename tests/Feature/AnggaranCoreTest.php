<?php

use App\Actions\Anggaran\BuildLaporanAnggaranRealisasi;
use App\Actions\Anggaran\SimpanAnggaran;
use App\Actions\Anggaran\ValidatePagu;
use App\Actions\Pembelian\ApprovePurchaseOrder;
use App\Enums\StatusPurchaseOrder;
use App\Models\Anggaran;
use App\Models\Facility;
use App\Models\GoodsReceipt;
use App\Models\Obat;
use App\Models\PurchaseOrder;
use App\Models\SumberDana;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

function makePurchaseOrder(
    int $sumberDanaId,
    float $total,
    StatusPurchaseOrder $status = StatusPurchaseOrder::Draft,
): PurchaseOrder {
    return PurchaseOrder::create([
        'no_po' => 'PO-ANGGARAN-'.uniqid(),
        'supplier_id' => Supplier::factory()->create()->id,
        'sumber_dana_id' => $sumberDanaId,
        'tanggal_po' => now()->toDateString(),
        'status' => $status,
        'total' => $total,
    ]);
}

test('saving budget revises version instead of creating duplicate', function () {
    $sumberDana = SumberDana::factory()->create();
    $action = app(SimpanAnggaran::class);

    $first = $action->handle(2026, $sumberDana->id, Anggaran::RS_FACILITY_ID, 1_000_000);
    $second = $action->handle(2026, $sumberDana->id, Anggaran::RS_FACILITY_ID, 1_500_000, 'Revisi pagu');

    expect($first->versi)->toBe(1)
        ->and($second->versi)->toBe(2)
        ->and((float) $second->jumlah_anggaran)->toBe(1_500_000.0)
        ->and(Anggaran::count())->toBe(1);

    expect(fn () => $action->handle(2026, $sumberDana->id, Anggaran::RS_FACILITY_ID, 2_000_000, allowUpdate: false))
        ->toThrow(ValidationException::class);
});

test('pagu validation separates commitment and realisation', function () {
    $sumberDana = SumberDana::factory()->create();
    app(SimpanAnggaran::class)->handle(2026, $sumberDana->id, Anggaran::RS_FACILITY_ID, 1_000_000);

    $received = makePurchaseOrder($sumberDana->id, 400_000, StatusPurchaseOrder::Dikirim);
    $outstanding = makePurchaseOrder($sumberDana->id, 300_000);

    $receipt = GoodsReceipt::create([
        'no_penerimaan' => 'GR-ANGGARAN-001',
        'po_id' => $received->id,
        'supplier_id' => $received->supplier_id,
        'sumber_dana_id' => $sumberDana->id,
        'tanggal_terima' => now()->toDateString(),
    ]);
    $receipt->items()->create([
        'obat_id' => Obat::factory()->create()->id,
        'no_batch' => 'BATCH-ANGGARAN',
        'tanggal_kedaluwarsa' => now()->addYear(),
        'jumlah' => 100,
        'harga_beli' => 1000,
        'sumber_dana_id' => $sumberDana->id,
        'subtotal' => 100_000,
    ]);

    $metrics = app(ValidatePagu::class)->handle(
        (int) now()->year,
        $sumberDana->id,
        Anggaran::RS_FACILITY_ID,
        50_000,
        $outstanding->id,
    );

    expect($metrics['pagu'])->toBe(1_000_000.0)
        ->and($metrics['komitmen'])->toBe(400_000.0)
        ->and($metrics['realisasi'])->toBe(100_000.0)
        ->and($metrics['komitmen_bersih'])->toBe(300_000.0)
        ->and($metrics['sisa'])->toBe(600_000.0)
        ->and($metrics['total_setelah'])->toBe(450_000.0)
        ->and($metrics['exceeded'])->toBeFalse();
});

test('po approval over pagu needs override reason and writes activity log', function () {
    $user = User::factory()->create();
    $sumberDana = SumberDana::factory()->create();
    app(SimpanAnggaran::class)->handle(2026, $sumberDana->id, Anggaran::RS_FACILITY_ID, 100_000);

    $po = makePurchaseOrder($sumberDana->id, 150_000);
    $action = app(ApprovePurchaseOrder::class);

    expect(fn () => $action->handle($po, $user->id))
        ->toThrow(ValidationException::class);

    $approved = $action->handle($po, $user->id, 'Kebutuhan outbreak');

    expect($approved->status)->toBe(StatusPurchaseOrder::Dikirim)
        ->and($approved->alasan_override_pagu)->toBe('Kebutuhan outbreak')
        ->and(Activity::where('log_name', 'anggaran')->count())->toBe(1);
});

test('budget realisation report sums rows and computes percentages', function () {
    $sumberDana = SumberDana::factory()->create();
    $facility = Facility::factory()->create(['jenis' => 'puskesmas']);
    app(SimpanAnggaran::class)->handle(2026, $sumberDana->id, Anggaran::RS_FACILITY_ID, 1_000_000);
    app(SimpanAnggaran::class)->handle(2026, $sumberDana->id, $facility->id, 250_000);

    makePurchaseOrder($sumberDana->id, 500_000, StatusPurchaseOrder::Dikirim);

    $report = app(BuildLaporanAnggaranRealisasi::class)->handle(2026);

    expect($report['pagu'])->toBe(1_250_000.0)
        ->and($report['komitmen'])->toBe(500_000.0)
        ->and($report['sisa'])->toBe(750_000.0)
        ->and($report['rows'])->toHaveCount(2);

    $rs = $report['rows']->firstWhere('anggaran.facility_id', Anggaran::RS_FACILITY_ID);

    expect($rs['persen_komitmen'])->toBe(50.0)
        ->and($rs['persen_realisasi'])->toBe(0.0);
});
