<?php

use App\Actions\Stock\ReceivePurchaseOrder;
use App\Enums\StatusPurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\Obat;
use App\Models\PurchaseOrder;
use App\Models\SumberDana;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function receiptUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function sentPurchaseOrder(): PurchaseOrder
{
    $obat = Obat::factory()->create();

    $po = PurchaseOrder::create([
        'no_po' => 'PO-GR-'.fake()->unique()->numerify('####'),
        'supplier_id' => Supplier::factory()->create()->id,
        'sumber_dana_id' => SumberDana::factory()->create()->id,
        'tanggal_po' => now()->toDateString(),
        'status' => StatusPurchaseOrder::Dikirim,
        'total' => 10000,
        'user_id' => receiptUser('gudang')->id,
        'approved_by' => receiptUser('apoteker')->id,
    ]);

    $po->items()->create([
        'obat_id' => $obat->id,
        'jumlah' => 10,
        'harga_beli' => 1000,
        'subtotal' => 10000,
    ]);

    return $po;
}

function makeReceipt(?PurchaseOrder $po = null): GoodsReceipt
{
    $po ??= sentPurchaseOrder();
    $item = $po->items()->first();

    return app(ReceivePurchaseOrder::class)->handle(
        $po,
        [[
            'po_item_id' => $item->id,
            'no_batch' => 'BATCH-1',
            'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
            'jumlah' => 4,
            'harga_beli' => 1000,
            'lokasi_rak' => 'R1',
        ]],
        receiptUser('gudang')->id,
    );
}

test('receipt index and create pages render', function () {
    $this->actingAs(receiptUser('gudang'))
        ->get(route('pembelian.penerimaan'))
        ->assertOk()
        ->assertSee('Penerimaan Barang');

    $this->actingAs(receiptUser('gudang'))
        ->get(route('pembelian.penerimaan.create'))
        ->assertOk()
        ->assertSee('Buat Penerimaan');
});

test('creating a receipt redirects to its detail page', function () {
    $po = sentPurchaseOrder();
    $item = $po->items()->first();

    $response = $this->actingAs(receiptUser('gudang'))->post(route('pembelian.penerimaan.store'), [
        'po_id' => $po->id,
        'tanggal_terima' => now()->toDateString(),
        'items' => [[
            'po_item_id' => $item->id,
            'no_batch' => 'BATCH-9',
            'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
            'jumlah' => 2,
            'harga_beli' => 1000,
            'lokasi_rak' => 'R2',
        ]],
    ]);

    $response->assertSessionHasNoErrors();
    $receipt = GoodsReceipt::firstOrFail();

    $response->assertRedirect(route('pembelian.penerimaan.show', $receipt));
    expect($receipt->items()->count())->toBe(1);
});

test('receipt detail shows items and totals', function () {
    $receipt = makeReceipt();

    $this->actingAs(receiptUser('gudang'))
        ->get(route('pembelian.penerimaan.show', $receipt))
        ->assertOk()
        ->assertSee($receipt->no_penerimaan)
        ->assertSee('Item diterima')
        ->assertSee('BATCH-1');
});

test('receipt edit page only exposes non-quantity fields', function () {
    $receipt = makeReceipt();

    $response = $this->actingAs(receiptUser('gudang'))
        ->get(route('pembelian.penerimaan.edit', $receipt))
        ->assertOk()
        ->assertSee('Hanya tanggal, catatan, dan lokasi rak yang dapat diubah.');

    expect($response->getContent())->not->toContain('name="items[0][jumlah]"');
});

test('updating a receipt changes only allowed fields', function () {
    $receipt = makeReceipt();
    $item = $receipt->items()->first();

    $this->actingAs(receiptUser('gudang'))
        ->put(route('pembelian.penerimaan.update', $receipt), [
            'tanggal_terima' => now()->subDay()->toDateString(),
            'catatan' => 'Revisi rak',
            'items' => [['lokasi_rak' => 'R9']],
        ])
        ->assertRedirect(route('pembelian.penerimaan.show', $receipt));

    $receipt->refresh();
    $item->refresh();

    expect($receipt->catatan)->toBe('Revisi rak')
        ->and($item->lokasi_rak)->toBe('R9')
        ->and($item->jumlah)->toBe(4);
});

test('receipt list returns a partial fragment for ajax filtering', function () {
    $receipt = makeReceipt();

    $response = $this->actingAs(receiptUser('gudang'))
        ->get(route('pembelian.penerimaan', ['partial' => 1]))
        ->assertOk();

    expect($response->getContent())
        ->toContain($receipt->no_penerimaan)
        ->not->toContain('<!doctype html>');
});

test('puskesmas cannot access receipt pages', function () {
    $receipt = makeReceipt();

    $this->actingAs(receiptUser('puskesmas'))
        ->get(route('pembelian.penerimaan.show', $receipt))
        ->assertForbidden();
});

test('receipt list can be filtered by search, supplier, and date range', function () {
    $first = makeReceipt();
    $second = makeReceipt();

    $user = receiptUser('gudang');

    $this->actingAs($user)
        ->get(route('pembelian.penerimaan', ['search' => $first->no_penerimaan]))
        ->assertOk()
        ->assertSee($first->no_penerimaan)
        ->assertDontSee($second->no_penerimaan);

    $this->actingAs($user)
        ->get(route('pembelian.penerimaan', ['supplier' => $first->supplier_id]))
        ->assertOk()
        ->assertSee($first->no_penerimaan)
        ->assertDontSee($second->no_penerimaan);

    $this->actingAs($user)
        ->get(route('pembelian.penerimaan', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee($first->no_penerimaan)
        ->assertSee($second->no_penerimaan);

    $this->actingAs($user)
        ->get(route('pembelian.penerimaan', ['from' => now()->addDay()->toDateString()]))
        ->assertOk()
        ->assertDontSee($first->no_penerimaan)
        ->assertDontSee($second->no_penerimaan);
});
