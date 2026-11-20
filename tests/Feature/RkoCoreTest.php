<?php

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Actions\Lplpo\VerifyLplpo;
use App\Actions\Rko\ApproveRko;
use App\Actions\Rko\BuildRko;
use App\Actions\Rko\GeneratePoFromRko;
use App\Actions\Rko\HitungPemakaianRataRata;
use App\Enums\StatusPurchaseOrder;
use App\Enums\StatusRko;
use App\Models\BatchObat;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\PurchaseOrder;
use App\Models\SumberDana;
use App\Models\Supplier;

test('average usage is calculated from verified lplpo periods', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();

    foreach ([10, 20, 30, 40, 50, 60] as $index => $pemakaian) {
        $lplpo = app(GenerateLplpo::class)->handle($facility->id, $index + 1, 2026, [[
            'obat_id' => $obat->id,
            'pemasukan' => 100,
            'pemakaian' => $pemakaian,
            'stok_akhir' => 50,
            'jumlah_diminta' => 0,
        ]]);

        app(SubmitLplpo::class)->handle($lplpo);
        app(VerifyLplpo::class)->handle($lplpo->refresh(), true);
    }

    expect(app(HitungPemakaianRataRata::class)->handle($obat->id))->toBe(35.0);
});

test('build rko calculates requirement and new version', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create(['harga_satuan_terakhir' => 1250, 'stok_minimum' => 0]);
    $supplier = Supplier::factory()->create();
    $sumberDana = SumberDana::factory()->create();

    $lplpo = app(GenerateLplpo::class)->handle($facility->id, 1, 2026, [[
        'obat_id' => $obat->id,
        'pemasukan' => 100,
        'pemakaian' => 70,
        'stok_akhir' => 30,
        'jumlah_diminta' => 0,
    ]]);
    app(SubmitLplpo::class)->handle($lplpo);
    app(VerifyLplpo::class)->handle($lplpo->refresh(), true);

    BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'RKO-001',
        'tanggal_kedaluwarsa' => now()->addYear(),
        'jumlah_awal' => 20,
        'jumlah_sisa' => 20,
        'harga_beli' => 1250,
        'aktif' => true,
    ]);

    $po = PurchaseOrder::create([
        'no_po' => 'PO-RKO-001',
        'supplier_id' => $supplier->id,
        'sumber_dana_id' => $sumberDana->id,
        'tanggal_po' => now()->toDateString(),
        'status' => StatusPurchaseOrder::Draft,
        'total' => 0,
    ]);
    $po->items()->create([
        'obat_id' => $obat->id,
        'jumlah' => 10,
        'harga_beli' => 1250,
        'subtotal' => 12500,
    ]);

    $rko = app(BuildRko::class)->handle(2026, 2, [
        'lead_time_bulan' => 2,
        'faktor_pengaman' => 1.5,
        'sumber_dana_id' => $sumberDana->id,
        'supplier_id' => $supplier->id,
    ]);

    $item = $rko->items->firstWhere('obat_id', $obat->id);

    expect($item->pemakaian_rata_rata)->toBe('70.00')
        ->and($item->estimasi_pemakaian)->toBe('70.00')
        ->and($item->stok_pengaman)->toBe('210.00')
        ->and($item->stok_akhir)->toBe(20)
        ->and($item->stok_dalam_pesanan)->toBe(10)
        ->and($item->kebutuhan_bersih)->toBe('250.00')
        ->and($rko->versi)->toBe(1);

    $second = app(BuildRko::class)->handle(2026, 2, [
        'sumber_dana_id' => $sumberDana->id,
        'supplier_id' => $supplier->id,
    ]);

    expect($second->versi)->toBe(2);
});

test('approved rko can generate draft purchase order', function () {
    $supplier = Supplier::factory()->create();
    $sumberDana = SumberDana::factory()->create();
    $obat = Obat::factory()->create(['stok_minimum' => 10, 'harga_satuan_terakhir' => 1000]);

    $rko = app(BuildRko::class)->handle(2026, 3, [
        'sumber_dana_id' => $sumberDana->id,
        'supplier_id' => $supplier->id,
    ]);

    expect($rko->status)->toBe(StatusRko::Draft);

    app(ApproveRko::class)->handle($rko);
    $orders = app(GeneratePoFromRko::class)->handle($rko->refresh());

    $po = $orders->first();

    expect($rko->refresh()->status)->toBe(StatusRko::Disetujui)
        ->and($po)->not->toBeNull()
        ->and($po->status)->toBe(StatusPurchaseOrder::Draft)
        ->and($po->items)->toHaveCount(1)
        ->and($po->items->first()->obat_id)->toBe($obat->id);
});
