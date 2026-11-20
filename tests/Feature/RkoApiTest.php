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

test('apoteker can build rko through api', function () {
    $user = User::factory()->create();
    $user->assignRole('apoteker');

    Obat::factory()->create();
    $supplier = Supplier::factory()->create();
    $sumberDana = SumberDana::factory()->create();

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/rko', [
        'tahun' => 2026,
        'periode' => 11,
        'lead_time_bulan' => 1,
        'faktor_pengaman' => 1,
        'sumber_dana_id' => $sumberDana->id,
        'supplier_id' => $supplier->id,
    ])->assertCreated()
        ->assertJsonPath('data.tahun', 2026)
        ->assertJsonPath('data.metode', 'historis')
        ->assertJsonStructure(['data' => ['items']]);
});

test('gudang cannot build rko', function () {
    $user = User::factory()->create();
    $user->assignRole('gudang');

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/rko', [
        'tahun' => 2026,
        'periode' => 11,
    ])->assertForbidden();
});
