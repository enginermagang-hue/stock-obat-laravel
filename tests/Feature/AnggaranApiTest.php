<?php

use App\Actions\Anggaran\SimpanAnggaran;
use App\Enums\StatusPurchaseOrder;
use App\Models\Anggaran;
use App\Models\Facility;
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

test('apoteker can manage budget through api', function () {
    $user = User::factory()->create();
    $user->assignRole('apoteker');
    $sumberDana = SumberDana::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/anggaran', [
        'sumber_dana_id' => $sumberDana->id,
        'tahun' => 2026,
        'facility_id' => Anggaran::RS_FACILITY_ID,
        'jumlah_anggaran' => 750_000_000,
        'keterangan' => 'Pagu awal',
    ])->assertCreated()
        ->assertJsonPath('data.tahun', 2026)
        ->assertJsonPath('data.facility_id', 0)
        ->assertJsonPath('data.versi', 1);

    $id = $response->json('data.id');

    $this->withToken($token)->postJson('/api/v1/anggaran', [
        'sumber_dana_id' => $sumberDana->id,
        'tahun' => 2026,
        'facility_id' => Anggaran::RS_FACILITY_ID,
        'jumlah_anggaran' => 800_000_000,
    ])->assertUnprocessable();

    $this->withToken($token)->putJson("/api/v1/anggaran/{$id}", [
        'jumlah_anggaran' => 800_000_000,
        'keterangan' => 'Revisi',
    ])->assertOk()
        ->assertJsonPath('data.versi', 2)
        ->assertJsonPath('data.jumlah_anggaran', 800_000_000);

    $this->withToken($token)->getJson('/api/v1/anggaran?tahun=2026')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->withToken($token)->getJson('/api/v1/anggaran/rekap?tahun=2026')
        ->assertOk()
        ->assertJsonPath('summary.pagu', 800_000_000);

    $this->withToken($token)->deleteJson("/api/v1/anggaran/{$id}")
        ->assertNoContent();
});

test('budget can target a puskesmas unit', function () {
    $user = User::factory()->create();
    $user->assignRole('apoteker');
    $facility = Facility::factory()->create(['jenis' => 'puskesmas']);
    $sumberDana = SumberDana::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/anggaran', [
        'sumber_dana_id' => $sumberDana->id,
        'tahun' => 2026,
        'facility_id' => $facility->id,
        'jumlah_anggaran' => 100_000_000,
    ])->assertCreated()
        ->assertJsonPath('data.unit', $facility->nama);
});

test('gudang cannot access budget api', function () {
    $user = User::factory()->create();
    $user->assignRole('gudang');
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/anggaran')->assertForbidden();
    $this->withToken($token)->getJson('/api/v1/anggaran/rekap')->assertForbidden();
});

test('po approval api requires override when pagu is exceeded', function () {
    $user = User::factory()->create();
    $user->assignRole('apoteker');
    $sumberDana = SumberDana::factory()->create();
    $supplier = Supplier::factory()->create();
    app(SimpanAnggaran::class)->handle(2026, $sumberDana->id, Anggaran::RS_FACILITY_ID, 100_000);

    $po = PurchaseOrder::create([
        'no_po' => 'PO-API-PAGU-001',
        'supplier_id' => $supplier->id,
        'sumber_dana_id' => $sumberDana->id,
        'tanggal_po' => now()->toDateString(),
        'status' => StatusPurchaseOrder::Draft,
        'total' => 150_000,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/purchase-orders/{$po->id}/approve")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('alasan_override_pagu');

    $this->withToken($token)->postJson("/api/v1/purchase-orders/{$po->id}/approve", [
        'alasan_override_pagu' => 'Kebutuhan darurat',
    ])->assertOk()
        ->assertJsonPath('data.status', 'dikirim');
});
