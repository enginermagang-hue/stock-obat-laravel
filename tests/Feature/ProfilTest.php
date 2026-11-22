<?php

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function profilUser(string $role, ?Facility $facility = null): User
{
    $user = User::factory()->create(['facility_id' => $facility?->id]);
    $user->assignRole($role);

    return $user;
}

test('user can update own nip and jabatan', function () {
    $user = profilUser('apoteker');

    $this->actingAs($user)
        ->get(route('profil'))
        ->assertOk()
        ->assertSee('Profil Saya');

    $this->actingAs($user)
        ->put(route('profil.update'), [
            'nip' => '198001012005011001',
            'jabatan' => 'Apoteker Penanggung Jawab',
        ])
        ->assertRedirect();

    expect($user->refresh()->nip)->toBe('198001012005011001')
        ->and($user->jabatan)->toBe('Apoteker Penanggung Jawab');
});

test('nip must be unique across users', function () {
    $first = profilUser('apoteker');
    $second = profilUser('gudang');

    $first->update(['nip' => '123']);

    $this->actingAs($second)
        ->put(route('profil.update'), ['nip' => '123'])
        ->assertSessionHasErrors('nip');
});

test('admin can update another user identity', function () {
    $admin = profilUser('admin');
    $target = profilUser('puskesmas', Facility::factory()->create(['jenis' => 'puskesmas']));

    $this->actingAs($admin)
        ->get(route('pengaturan.pengguna'))
        ->assertOk()
        ->assertSee($target->name);

    $this->actingAs($admin)
        ->put(route('pengaturan.pengguna.update', $target), [
            'nip' => '555',
            'jabatan' => 'Kepala Puskesmas',
        ])
        ->assertRedirect();

    expect($target->refresh()->nip)->toBe('555')
        ->and($target->jabatan)->toBe('Kepala Puskesmas');
});

test('non admin cannot manage user identities', function () {
    $target = profilUser('puskesmas', Facility::factory()->create(['jenis' => 'puskesmas']));

    $this->actingAs(profilUser('apoteker'))
        ->get(route('pengaturan.pengguna'))
        ->assertForbidden();

    $this->actingAs(profilUser('gudang'))
        ->put(route('pengaturan.pengguna.update', $target), ['nip' => '999'])
        ->assertForbidden();
});
