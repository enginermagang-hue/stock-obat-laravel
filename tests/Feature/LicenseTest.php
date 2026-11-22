<?php

use App\Models\License;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('license status reflects expiry date', function () {
    $valid = License::factory()->create(['tanggal_kedaluwarsa' => today()->addYear()]);
    $soon = License::factory()->expiringSoon(10)->create();
    $expired = License::factory()->expired()->create();

    expect($valid->status)->toBe('valid')
        ->and($soon->status)->toBe('segera kedaluwarsa')
        ->and($expired->status)->toBe('kedaluwarsa')
        ->and(License::expired()->count())->toBe(1)
        ->and(License::expiring(30)->count())->toBe(1);
});

test('license expiry command notifies pharmacist', function () {
    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');

    License::factory()->expiringSoon(10)->create();

    Artisan::call('licenses:check-expiry');

    expect($apoteker->notifications()->count())->toBeGreaterThan(0);
});

test('apoteker can manage licenses through the api', function () {
    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');

    $this->actingAs($apoteker)
        ->postJson('/api/v1/master/licenses', [
            'jenis' => 'cdob',
            'nomor' => 'CDOB-2026-001',
            'tanggal_terbit' => today()->subYear()->toDateString(),
            'tanggal_kedaluwarsa' => today()->addYear()->toDateString(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.nomor', 'CDOB-2026-001');

    expect(License::count())->toBe(1)
        ->and(License::first()->nomor)->toBe('CDOB-2026-001');
});

test('puskesmas cannot open license page', function () {
    $user = User::factory()->create();
    $user->assignRole('puskesmas');

    $this->actingAs($user)->get('/master/licenses')->assertForbidden();
});
