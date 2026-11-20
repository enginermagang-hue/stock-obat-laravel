<?php

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function lplpoToken(string $role, ?int $facilityId = null): string
{
    $user = User::factory()->create(['facility_id' => $facilityId]);
    $user->assignRole($role);

    return $user->createToken('test')->plainTextToken;
}

test('puskesmas lplpo api is scoped to own facility', function () {
    $a = Facility::factory()->create();
    $b = Facility::factory()->create();
    $obat = Obat::factory()->create();

    $tokenA = lplpoToken('puskesmas', $a->id);
    $tokenB = lplpoToken('puskesmas', $b->id);

    $this->withToken($tokenA)->postJson('/api/v1/lplpo', [
        'facility_id' => $b->id,
        'bulan' => 11,
        'tahun' => 2026,
        'items' => [[
            'obat_id' => $obat->id,
            'pemakaian' => 10,
            'stok_akhir' => 20,
            'jumlah_diminta' => 30,
        ]],
    ])->assertCreated()
        ->assertJsonPath('data.facility_id', $a->id);

    Illuminate\Support\Facades\Auth::forgetGuards();
    $this->withToken($tokenB)->getJson('/api/v1/lplpo')->assertOk()->assertJsonCount(0, 'data');

    Illuminate\Support\Facades\Auth::forgetGuards();
    $this->withToken($tokenA)->getJson('/api/v1/lplpo')->assertOk()->assertJsonCount(1, 'data');
});

test('only apoteker can verify lplpo', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();

    $lplpo = app(GenerateLplpo::class)->handle($facility->id, 11, 2026, [[
        'obat_id' => $obat->id,
        'pemakaian' => 10,
        'stok_akhir' => 20,
        'jumlah_diminta' => 0,
    ]]);
    app(SubmitLplpo::class)->handle($lplpo);

    $puskesmas = lplpoToken('puskesmas', $facility->id);
    $apoteker = lplpoToken('apoteker');

    $this->withToken($puskesmas)->postJson("/api/v1/lplpo/{$lplpo->id}/verify", ['approve' => true])
        ->assertForbidden();

    Illuminate\Support\Facades\Auth::forgetGuards();
    $this->withToken($apoteker)->postJson("/api/v1/lplpo/{$lplpo->id}/verify", ['approve' => true])
        ->assertOk()
        ->assertJsonPath('data.status', 'diverifikasi');
});
