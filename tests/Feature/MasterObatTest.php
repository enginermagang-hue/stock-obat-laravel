<?php

use App\Models\Obat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function obatAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('master obat page renders without zebra rows', function () {
    $this->actingAs(obatAdmin())
        ->get(route('master.obat'))
        ->assertOk()
        ->assertSee('Master Obat')
        ->assertSee('Actions')
        ->assertDontSee('os-table--striped')
        ->assertDontSee('wire:');
});

test('master obat api lists obat with pagination meta', function () {
    Obat::factory()->count(3)->create();

    $this->actingAs(obatAdmin())
        ->getJson('/api/v1/master/obat')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 3);
});

test('master obat api creates a new obat', function () {
    $this->actingAs(obatAdmin())
        ->postJson('/api/v1/master/obat', [
            'kode_obat' => 'OBT-API-1',
            'nama' => 'Paracetamol',
            'satuan' => 'tablet',
            'bentuk_sediaan' => 'tablet',
            'golongan' => 'bebas',
            'aktif' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.kode_obat', 'OBT-API-1');

    expect(Obat::where('kode_obat', 'OBT-API-1')->exists())->toBeTrue();
});

test('master obat api validates required fields', function () {
    $this->actingAs(obatAdmin())
        ->postJson('/api/v1/master/obat', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['kode_obat', 'nama', 'satuan', 'bentuk_sediaan', 'golongan']);
});

test('master obat api updates and deletes a record', function () {
    $obat = Obat::factory()->create();

    $this->actingAs(obatAdmin())
        ->putJson('/api/v1/master/obat/'.$obat->id, [
            'kode_obat' => $obat->kode_obat,
            'nama' => 'Nama Baru',
            'satuan' => 'tablet',
            'bentuk_sediaan' => 'tablet',
            'golongan' => 'bebas',
            'aktif' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.nama', 'Nama Baru');

    $this->actingAs(obatAdmin())
        ->deleteJson('/api/v1/master/obat/'.$obat->id)
        ->assertOk();

    expect(Obat::count())->toBe(0);
});

test('users without manage-master cannot write through the api', function () {
    $user = User::factory()->create();
    $user->assignRole('distribusi');

    $this->actingAs($user)
        ->postJson('/api/v1/master/obat', [
            'kode_obat' => 'OBT-X',
            'nama' => 'X',
            'satuan' => 'tablet',
            'bentuk_sediaan' => 'tablet',
            'golongan' => 'bebas',
        ])
        ->assertForbidden();
});
