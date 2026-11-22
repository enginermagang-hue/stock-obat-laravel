<?php

use App\Enums\StatusLplpo;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function reportToken(string $role, ?int $facilityId = null): string
{
    $user = User::factory()->create(['facility_id' => $facilityId]);
    $user->assignRole($role);

    return $user->createToken('test')->plainTextToken;
}

test('report api requires authentication', function () {
    $this->getJson('/api/v1/reports/anggaran-realisasi')->assertUnauthorized();
});

test('apoteker can read reports through api', function () {
    $token = reportToken('apoteker');

    $this->withToken($token)
        ->getJson('/api/v1/reports/anggaran-realisasi?tahun=2026')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['report', 'title', 'columns', 'headings', 'rows'],
            'meta' => ['count'],
        ]);
});

test('puskesmas cannot read warehouse stock report', function () {
    $facility = Facility::factory()->create();
    $token = reportToken('puskesmas', $facility->id);

    $this->withToken($token)
        ->getJson('/api/v1/reports/stock-summary')
        ->assertForbidden();
});

test('puskesmas report is scoped to own facility', function () {
    $facilityA = Facility::factory()->create();
    $facilityB = Facility::factory()->create();
    $obat = Obat::factory()->create();

    $lplpo = Lplpo::create([
        'no_lplpo' => 'LPLPO-A-001',
        'facility_id' => $facilityA->id,
        'bulan' => 11,
        'tahun' => 2026,
        'status' => StatusLplpo::Diverifikasi,
    ]);

    $lplpo->items()->create([
        'obat_id' => $obat->id,
        'pemakaian' => 5,
        'stok_akhir' => 1,
        'stok_akhir_sistem' => 1,
        'jumlah_diminta' => 0,
    ]);

    $tokenB = reportToken('puskesmas', $facilityB->id);

    $this->withToken($tokenB)
        ->getJson('/api/v1/reports/lplpo-recap?tahun=2026')
        ->assertOk()
        ->assertJsonPath('meta.count', 0);
});

test('audit log report requires audit permission', function () {
    $puskesmas = reportToken('puskesmas', Facility::factory()->create()->id);
    $apoteker = reportToken('apoteker');

    $this->withToken($puskesmas)
        ->getJson('/api/v1/reports/audit-log')
        ->assertForbidden();

    Auth::forgetGuards();

    $this->withToken($apoteker)
        ->getJson('/api/v1/reports/audit-log')
        ->assertOk();
});

test('report export supports excel and pdf', function () {
    $token = reportToken('apoteker');

    $this->withToken($token)
        ->get('/api/v1/reports/anggaran-realisasi/export?format=xlsx')
        ->assertOk();

    Auth::forgetGuards();

    $this->withToken($token)
        ->get('/api/v1/reports/anggaran-realisasi/export?format=pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
