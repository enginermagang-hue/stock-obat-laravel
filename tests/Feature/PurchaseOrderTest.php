<?php

use App\Enums\StatusPurchaseOrder;
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

function poUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makePo(array $attributes = []): PurchaseOrder
{
    $obat = Obat::factory()->create();

    $po = PurchaseOrder::create(array_merge([
        'no_po' => 'PO-TEST-'.fake()->unique()->numerify('####'),
        'supplier_id' => Supplier::factory()->create()->id,
        'sumber_dana_id' => SumberDana::factory()->create()->id,
        'tanggal_po' => now()->toDateString(),
        'status' => StatusPurchaseOrder::Draft,
        'total' => 1000,
        'user_id' => poUser('gudang')->id,
    ], $attributes));

    $po->items()->create([
        'obat_id' => $obat->id,
        'jumlah' => 10,
        'harga_beli' => 100,
        'subtotal' => 1000,
    ]);

    return $po;
}

test('purchase order index and create pages render', function () {
    $this->actingAs(poUser('gudang'))
        ->get(route('pembelian.purchase-order'))
        ->assertOk()
        ->assertSee('Purchase Order');

    $this->actingAs(poUser('gudang'))
        ->get(route('pembelian.purchase-order.create'))
        ->assertOk()
        ->assertSee('Buat PO')
        ->assertSee('Bentuk sediaan')
        ->assertSee('Satuan')
        ->assertSee('Total')
        ->assertSee('Tambah item');
});

test('gudang can create a purchase order and is redirected to detail', function () {
    $supplier = Supplier::factory()->create();
    $dana = SumberDana::factory()->create();
    $obat = Obat::factory()->create();

    $response = $this->actingAs(poUser('gudang'))->post(route('pembelian.purchase-order.store'), [
        'supplier_id' => $supplier->id,
        'sumber_dana_id' => $dana->id,
        'tanggal_po' => now()->toDateString(),
        'items' => [
            ['obat_id' => $obat->id, 'jumlah' => 5, 'harga_beli' => 2000],
        ],
    ]);

    $po = PurchaseOrder::firstOrFail();

    $response->assertRedirect(route('pembelian.purchase-order.show', $po));
    expect($po->status)->toBe(StatusPurchaseOrder::Draft)
        ->and((float) $po->total)->toBe(10000.0)
        ->and($po->items()->count())->toBe(1);
});

test('detail page shows items and totals', function () {
    $po = makePo();

    $this->actingAs(poUser('gudang'))
        ->get(route('pembelian.purchase-order.show', $po))
        ->assertOk()
        ->assertSee($po->no_po)
        ->assertSee('Item PO')
        ->assertSee($po->items->first()->obat->nama);
});

test('draft and rejected purchase orders can be edited', function () {
    foreach ([StatusPurchaseOrder::Draft, StatusPurchaseOrder::Ditolak] as $status) {
        $po = makePo(['status' => $status]);

        $this->actingAs(poUser('gudang'))
            ->get(route('pembelian.purchase-order.edit', $po))
            ->assertOk()
            ->assertSee('Ubah PO '.$po->no_po);
    }
});

test('sent purchase orders cannot be edited', function () {
    $po = makePo(['status' => StatusPurchaseOrder::Dikirim]);

    $this->actingAs(poUser('gudang'))
        ->get(route('pembelian.purchase-order.edit', $po))
        ->assertForbidden();
});

test('updating a rejected purchase order recalculates totals and resets to draft', function () {
    $po = makePo(['status' => StatusPurchaseOrder::Ditolak]);
    $obat = Obat::factory()->create();

    $this->actingAs(poUser('gudang'))->put(route('pembelian.purchase-order.update', $po), [
        'supplier_id' => $po->supplier_id,
        'sumber_dana_id' => $po->sumber_dana_id,
        'tanggal_po' => now()->toDateString(),
        'items' => [
            ['obat_id' => $obat->id, 'jumlah' => 3, 'harga_beli' => 5000],
        ],
    ])->assertRedirect(route('pembelian.purchase-order.show', $po));

    $po->refresh();

    expect($po->status)->toBe(StatusPurchaseOrder::Draft)
        ->and((float) $po->total)->toBe(15000.0)
        ->and($po->items()->count())->toBe(1);
});

test('apoteker can reject a draft purchase order', function () {
    $po = makePo();

    $this->actingAs(poUser('apoteker'))
        ->post(route('pembelian.purchase-order.reject', $po))
        ->assertRedirect(route('pembelian.purchase-order.show', $po));

    expect($po->refresh()->status)->toBe(StatusPurchaseOrder::Ditolak);
});

test('puskesmas cannot access purchase order pages', function () {
    $po = makePo();

    $this->actingAs(poUser('puskesmas'))
        ->get(route('pembelian.purchase-order'))
        ->assertForbidden();
});
