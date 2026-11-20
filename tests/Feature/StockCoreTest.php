<?php

use App\Actions\Opname\CreateAdjustmentFromOpname;
use App\Actions\Opname\GenerateOpname;
use App\Actions\Opname\InputStokFisik;
use App\Actions\Opname\VerifyOpname;
use App\Actions\Stock\ProcessStockAdjustment;
use App\Actions\Stock\ReceivePurchaseOrder;
use App\Enums\ModeOpname;
use App\Enums\StatusOpname;
use App\Enums\StatusPenyesuaian;
use App\Enums\StatusPurchaseOrder;
use App\Enums\TipeMutasi;
use App\Enums\TipePenyesuaian;
use App\Enums\TipeReferensi;
use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\SumberDana;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Stock\StockLedgerService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

function makeBatch(int $stok = 0): BatchObat
{
    return BatchObat::create([
        'obat_id' => Obat::factory()->create()->id,
        'no_batch' => 'BATCH-'.uniqid(),
        'tanggal_kedaluwarsa' => now()->addYear(),
        'jumlah_awal' => $stok,
        'jumlah_sisa' => $stok,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);
}

test('stock ledger updates batch and keeps before after values', function () {
    $batch = makeBatch();
    $service = app(StockLedgerService::class);

    $service->record($batch, TipeMutasi::Masuk, 100, TipeReferensi::Penerimaan, 1);
    $service->record($batch, TipeMutasi::Keluar, 30, TipeReferensi::Penyesuaian, 2);

    expect($batch->refresh()->jumlah_sisa)->toBe(70);

    $entries = $batch->ledger()->orderBy('id')->get();
    expect($entries)->toHaveCount(2)
        ->and($entries[0]->jumlah_sebelum)->toBe(0)
        ->and($entries[0]->jumlah_sesudah)->toBe(100)
        ->and($entries[1]->jumlah_sebelum)->toBe(100)
        ->and($entries[1]->jumlah_sesudah)->toBe(70);
});

test('stock ledger rejects insufficient stock', function () {
    $batch = makeBatch(5);
    $service = app(StockLedgerService::class);

    expect(fn () => $service->record($batch, TipeMutasi::Keluar, 10, TipeReferensi::Penyesuaian, 1))
        ->toThrow(ValidationException::class);

    expect($batch->refresh()->jumlah_sisa)->toBe(5);
});

test('receiving purchase order creates batch ledger and updates status', function () {
    $supplier = Supplier::factory()->create();
    $sumberDana = SumberDana::factory()->create();
    $obat = Obat::factory()->create();

    $po = PurchaseOrder::create([
        'no_po' => 'PO-TEST-001',
        'supplier_id' => $supplier->id,
        'sumber_dana_id' => $sumberDana->id,
        'tanggal_po' => now()->toDateString(),
        'status' => StatusPurchaseOrder::Dikirim,
        'total' => 50000,
    ]);

    $item = $po->items()->create([
        'obat_id' => $obat->id,
        'jumlah' => 50,
        'harga_beli' => 1000,
        'subtotal' => 50000,
    ]);

    $receipt = app(ReceivePurchaseOrder::class)->handle($po, [[
        'po_item_id' => $item->id,
        'no_batch' => 'RCV-001',
        'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
        'jumlah' => 50,
        'harga_beli' => 1000,
        'lokasi_rak' => 'Rak A',
    ]]);

    expect($receipt->items)->toHaveCount(1)
        ->and($po->refresh()->status)->toBe(StatusPurchaseOrder::Diterima)
        ->and($item->refresh()->jumlah_diterima)->toBe(50);

    $batch = BatchObat::where('no_batch', 'RCV-001')->first();
    expect($batch)->not->toBeNull()
        ->and($batch->jumlah_sisa)->toBe(50)
        ->and($batch->ledger()->count())->toBe(1);
});

test('stock adjustment processing writes ledger', function () {
    $batch = makeBatch();
    app(StockLedgerService::class)->record($batch, TipeMutasi::Masuk, 40, TipeReferensi::Penerimaan, 1);

    $adjustment = StockAdjustment::create([
        'no_penyesuaian' => 'ADJ-TEST-001',
        'tipe' => TipePenyesuaian::Koreksi,
        'tanggal' => now()->toDateString(),
        'status' => StatusPenyesuaian::Draft,
    ]);
    $adjustment->items()->create([
        'batch_id' => $batch->id,
        'obat_id' => $batch->obat_id,
        'jumlah' => -5,
        'alasan' => 'uji koreksi',
        'nilai' => -5000,
    ]);

    app(ProcessStockAdjustment::class)->handle($adjustment);

    expect($adjustment->refresh()->status)->toBe(StatusPenyesuaian::Diproses)
        ->and($batch->refresh()->jumlah_sisa)->toBe(35)
        ->and($batch->ledger()->count())->toBe(2);
});

test('stock alert command creates database notifications', function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $obat = Obat::factory()->create(['stok_minimum' => 100]);

    BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'EXP-001',
        'tanggal_kedaluwarsa' => now()->addDays(10),
        'jumlah_awal' => 1,
        'jumlah_sisa' => 1,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    Artisan::call('stock:check-alerts');

    expect($admin->notifications()->count())->toBeGreaterThan(0);
});

test('blind opname requires all physical stock and creates adjustment', function () {
    $batch = makeBatch(20);

    $opname = app(GenerateOpname::class)->handle(
        now()->month,
        now()->year,
        mode: ModeOpname::Buta,
    );

    expect($opname->items)->toHaveCount(1);

    $item = $opname->items->first();

    expect(fn () => app(InputStokFisik::class)->handle($opname, [$item->id => null]))
        ->toThrow(ValidationException::class);

    $opname = app(InputStokFisik::class)->handle($opname, [$item->id => 18]);

    expect($opname->status)->toBe(StatusOpname::Proses)
        ->and($opname->items->first()->selisih)->toBe(-2);

    app(VerifyOpname::class)->handle($opname);
    $adjustment = app(CreateAdjustmentFromOpname::class)->handle($opname->refresh());

    expect($adjustment->items)->toHaveCount(1)
        ->and($batch->refresh()->jumlah_sisa)->toBe(18)
        ->and($opname->refresh()->status)->toBe(StatusOpname::Selesai);
});
