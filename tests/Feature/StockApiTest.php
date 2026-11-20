<?php

use App\Models\Obat;
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

function tokenFor(string $role): string
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user->createToken('test')->plainTextToken;
}

test('stock api requires authentication', function () {
    $this->getJson('/api/v1/batch-obat')->assertUnauthorized();
});

test('gudang can read batch and ledger endpoints', function () {
    $token = tokenFor('gudang');

    $this->withToken($token)->getJson('/api/v1/batch-obat')->assertOk()->assertJsonStructure(['data']);
    $this->withToken($token)->getJson('/api/v1/stock-ledger')->assertOk()->assertJsonStructure(['data']);
});

test('puskesmas cannot access stock endpoints', function () {
    $token = tokenFor('puskesmas');

    $this->withToken($token)->getJson('/api/v1/batch-obat')->assertForbidden();
    $this->withToken($token)->getJson('/api/v1/stock-ledger')->assertForbidden();
});

test('purchase order can be created through api', function () {
    $token = tokenFor('gudang');
    $obat = Obat::factory()->create();
    $supplier = Supplier::factory()->create();
    $sumberDana = SumberDana::factory()->create();

    $this->withToken($token)->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->id,
        'sumber_dana_id' => $sumberDana->id,
        'tanggal_po' => now()->toDateString(),
        'items' => [[
            'obat_id' => $obat->id,
            'jumlah' => 10,
            'harga_beli' => 1000,
        ]],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'draft');
});
