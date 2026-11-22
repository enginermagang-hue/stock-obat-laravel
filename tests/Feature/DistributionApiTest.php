<?php

use App\Actions\Permintaan\FulfillPermintaan;
use App\Enums\StatusItemPermintaan;
use App\Enums\StatusPermintaan;
use App\Enums\SumberPermintaan;
use App\Models\BatchObat;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function distributionApiToken(string $role, ?int $facilityId = null): string
{
    $user = User::factory()->create(['facility_id' => $facilityId]);
    $user->assignRole($role);

    return $user->createToken('test')->plainTextToken;
}

function distributionApiBatch(Obat $obat, int $stok, string $expiry, float $harga = 1000): BatchObat
{
    return BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'API-'.uniqid(),
        'tanggal_kedaluwarsa' => $expiry,
        'jumlah_awal' => $stok,
        'jumlah_sisa' => $stok,
        'harga_beli' => $harga,
        'aktif' => true,
    ]);
}

function distributionApiPermintaan(Facility $facility, Obat $obat, int $qty): PermintaanObat
{
    $permintaan = PermintaanObat::create([
        'no_permintaan' => 'PMSK-'.uniqid(),
        'facility_id' => $facility->id,
        'sumber' => SumberPermintaan::Mandiri,
        'tanggal' => now()->toDateString(),
        'status' => StatusPermintaan::Disetujui,
    ]);

    $permintaan->items()->create([
        'obat_id' => $obat->id,
        'jumlah_diminta' => $qty,
        'jumlah_disetujui' => $qty,
        'status_item' => StatusItemPermintaan::Disetujui,
    ]);

    return $permintaan->load('items');
}

test('distribution api requires authentication', function () {
    $this->getJson('/api/v1/distributions')->assertUnauthorized();
});

test('distribusi can create surat jalan from approved permintaan', function () {
    $distribusi = distributionApiToken('distribusi');
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();
    distributionApiBatch($obat, 50, now()->addYear()->toDateString());
    $permintaan = distributionApiPermintaan($facility, $obat, 20);

    $response = $this->withToken($distribusi)->postJson('/api/v1/distributions', [
        'facility_id' => $facility->id,
        'permintaan_id' => $permintaan->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.facility_id', $facility->id)
        ->assertJsonPath('data.items.0.jumlah', 20);

    expect($permintaan->refresh()->status)->toBe(StatusPermintaan::Diproses);
});

test('distribution lifecycle approve send and confirm', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();
    $batch = distributionApiBatch($obat, 50, now()->addYear()->toDateString());
    $permintaan = distributionApiPermintaan($facility, $obat, 20);

    $distribution = app(FulfillPermintaan::class)->handle($permintaan);

    $apoteker = distributionApiToken('apoteker');
    $distribusi = distributionApiToken('distribusi');
    $puskesmas = distributionApiToken('puskesmas', $facility->id);

    $this->withToken($apoteker)
        ->postJson("/api/v1/distributions/{$distribution->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'disetujui');

    Auth::forgetGuards();

    $this->withToken($distribusi)
        ->postJson("/api/v1/distributions/{$distribution->id}/send")
        ->assertOk()
        ->assertJsonPath('data.status', 'dikirim');

    expect($batch->refresh()->jumlah_sisa)->toBe(30)
        ->and($distribution->refresh()->no_sbbk)->toStartWith('SBBK-')
        ->and($distribution->tanggal_keluar)->not->toBeNull();

    Auth::forgetGuards();

    $this->withToken($puskesmas)
        ->postJson("/api/v1/distributions/{$distribution->id}/confirm", ['penerima_nama' => 'Petugas PKM'])
        ->assertOk()
        ->assertJsonPath('data.status', 'diterima')
        ->assertJsonPath('data.penerima_nama', 'Petugas PKM');

    expect($permintaan->refresh()->status)->toBe(StatusPermintaan::Selesai)
        ->and($distribution->refresh()->no_bast)->toStartWith('BAST-');
});

test('puskesmas cannot create distribution', function () {
    $facility = Facility::factory()->create();
    $token = distributionApiToken('puskesmas', $facility->id);

    $this->withToken($token)->postJson('/api/v1/distributions', [
        'facility_id' => $facility->id,
        'items' => [['obat_id' => Obat::factory()->create()->id, 'jumlah' => 1]],
    ])->assertForbidden();
});

test('distribution api rejects insufficient stock', function () {
    $token = distributionApiToken('distribusi');
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();
    distributionApiBatch($obat, 5, now()->addYear()->toDateString());

    $this->withToken($token)->postJson('/api/v1/distributions', [
        'facility_id' => $facility->id,
        'items' => [['obat_id' => $obat->id, 'jumlah' => 10]],
    ])->assertStatus(422)->assertJsonValidationErrors('items');
});

test('puskesmas only sees distributions for own facility', function () {
    $facilityA = Facility::factory()->create();
    $facilityB = Facility::factory()->create();
    $obat = Obat::factory()->create();
    distributionApiBatch($obat, 50, now()->addYear()->toDateString());

    $permintaan = distributionApiPermintaan($facilityA, $obat, 10);
    app(FulfillPermintaan::class)->handle($permintaan);

    $tokenB = distributionApiToken('puskesmas', $facilityB->id);

    $this->withToken($tokenB)
        ->getJson('/api/v1/distributions')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('surat jalan pdf can be downloaded by authorized role', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();
    distributionApiBatch($obat, 50, now()->addYear()->toDateString());
    $permintaan = distributionApiPermintaan($facility, $obat, 10);

    $distribution = app(FulfillPermintaan::class)->handle($permintaan);

    $token = distributionApiToken('distribusi');

    $this->withToken($token)
        ->get("/api/v1/distributions/{$distribution->id}/surat-jalan")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
