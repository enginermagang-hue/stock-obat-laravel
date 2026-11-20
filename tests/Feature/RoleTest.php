<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

test('role permissions follow the blueprint matrix', function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');

    $puskesmas = User::factory()->create();
    $puskesmas->assignRole('puskesmas');

    expect($admin->can('manage-users'))->toBeTrue()
        ->and($admin->can('manage-master'))->toBeTrue()
        ->and($apoteker->can('verify-permintaan'))->toBeTrue()
        ->and($apoteker->can('manage-users'))->toBeFalse()
        ->and($puskesmas->can('create-lplpo'))->toBeTrue()
        ->and($puskesmas->can('manage-master'))->toBeFalse()
        ->and($puskesmas->can('verify-lplpo'))->toBeFalse();
});

test('puskesmas cannot open master data pages', function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->assignRole('puskesmas');

    $this->actingAs($user)->get('/master/obat')->assertForbidden();
});
