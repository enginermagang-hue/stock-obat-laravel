<?php

use App\Enums\StatusDistribusi;
use App\Enums\StatusLplpo;
use App\Models\BatchObat;
use App\Models\Distribution;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Models\User;
use App\Scopes\FacilityScope;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function lplpoPageUser(string $role, ?Facility $facility = null): User
{
    $user = User::factory()->create(['facility_id' => $facility?->id]);
    $user->assignRole($role);

    return $user;
}

function lplpoPageFacility(): Facility
{
    return Facility::factory()->create(['jenis' => 'puskesmas']);
}

function lplpoPageRecord(StatusLplpo $status = StatusLplpo::Draft, ?Facility $facility = null): Lplpo
{
    $facility ??= lplpoPageFacility();
    $obat = Obat::factory()->create();

    $lplpo = Lplpo::create([
        'no_lplpo' => 'LPL-TEST-'.fake()->unique()->numerify('####'),
        'facility_id' => $facility->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'status' => $status,
        'versi' => 1,
    ]);

    $lplpo->items()->create([
        'obat_id' => $obat->id,
        'stok_awal' => 10,
        'pemasukan' => 5,
        'pemakaian' => 3,
        'stok_akhir' => 12,
        'stok_akhir_sistem' => 12,
        'stok_minimum' => 2,
        'jumlah_diminta' => 20,
    ]);

    return $lplpo;
}

test('lplpo index and create pages render', function () {
    $facility = lplpoPageFacility();

    $this->actingAs(lplpoPageUser('puskesmas', $facility))
        ->get(route('lplpo.index'))
        ->assertOk()
        ->assertSee('LPLPO');

    $this->actingAs(lplpoPageUser('puskesmas', $facility))
        ->get(route('lplpo.create'))
        ->assertOk()
        ->assertSee('Buat LPLPO');
});

test('creating an lplpo redirects to its detail page', function () {
    $facility = lplpoPageFacility();
    $obat = Obat::factory()->create();
    $user = lplpoPageUser('puskesmas', $facility);

    $response = $this->actingAs($user)->post(route('lplpo.store'), [
        'facility_id' => $facility->id,
        'bulan' => now()->month,
        'tahun' => now()->year,
        'items' => [[
            'obat_id' => $obat->id,
            'pemasukan' => 5,
            'pemakaian' => 2,
            'jumlah_diminta' => 10,
        ]],
    ]);

    $lplpo = Lplpo::withoutGlobalScope(FacilityScope::class)->firstOrFail();

    $response->assertRedirect(route('lplpo.show', $lplpo));
    expect($lplpo->items()->count())->toBe(1);
});

test('detail page shows items and status', function () {
    $lplpo = lplpoPageRecord();

    $this->actingAs(lplpoPageUser('apoteker'))
        ->get(route('lplpo.show', $lplpo))
        ->assertOk()
        ->assertSee($lplpo->no_lplpo)
        ->assertSee('Item LPLPO')
        ->assertSee($lplpo->items->first()->obat->nama);
});

test('draft lplpo can be edited', function () {
    $facility = lplpoPageFacility();
    $lplpo = lplpoPageRecord(StatusLplpo::Draft, $facility);

    $this->actingAs(lplpoPageUser('puskesmas', $facility))
        ->get(route('lplpo.edit', $lplpo))
        ->assertOk()
        ->assertSee('Ubah LPLPO '.$lplpo->no_lplpo);
});

test('rejected lplpo can be edited', function () {
    $facility = lplpoPageFacility();
    $lplpo = lplpoPageRecord(StatusLplpo::Ditolak, $facility);

    $this->actingAs(lplpoPageUser('puskesmas', $facility))
        ->get(route('lplpo.edit', $lplpo))
        ->assertOk()
        ->assertSee('Ubah LPLPO '.$lplpo->no_lplpo);
});

test('verified lplpo cannot be edited', function () {
    $facility = lplpoPageFacility();
    $lplpo = lplpoPageRecord(StatusLplpo::Diverifikasi, $facility);

    $this->actingAs(lplpoPageUser('puskesmas', $facility))
        ->get(route('lplpo.edit', $lplpo))
        ->assertForbidden();
});

test('updating a rejected lplpo resets it to draft and replaces items', function () {
    $facility = lplpoPageFacility();
    $lplpo = lplpoPageRecord(StatusLplpo::Ditolak, $facility);
    $obat = Obat::factory()->create();

    $this->actingAs(lplpoPageUser('puskesmas', $facility))
        ->put(route('lplpo.update', $lplpo), [
            'facility_id' => $facility->id,
            'bulan' => $lplpo->bulan,
            'tahun' => $lplpo->tahun,
            'catatan' => 'Revisi',
            'items' => [[
                'obat_id' => $obat->id,
                'pemasukan' => 1,
                'pemakaian' => 1,
                'jumlah_diminta' => 4,
            ]],
        ])
        ->assertRedirect(route('lplpo.show', $lplpo));

    $lplpo->refresh();

    expect($lplpo->status)->toBe(StatusLplpo::Draft)
        ->and($lplpo->catatan)->toBe('Revisi')
        ->and($lplpo->items()->count())->toBe(1)
        ->and($lplpo->items()->first()->obat_id)->toBe($obat->id);
});

test('item lookup fills previous stock and incoming distribution', function () {
    $facility = lplpoPageFacility();
    $user = lplpoPageUser('puskesmas', $facility);
    $obat = Obat::factory()->create();

    $previous = Lplpo::create([
        'no_lplpo' => 'LPL-PREV-0001',
        'facility_id' => $facility->id,
        'bulan' => now()->subMonthNoOverflow()->month,
        'tahun' => now()->subMonthNoOverflow()->year,
        'status' => StatusLplpo::Diverifikasi,
        'versi' => 1,
    ]);
    $previous->items()->create([
        'obat_id' => $obat->id,
        'stok_awal' => 0,
        'pemasukan' => 0,
        'pemakaian' => 0,
        'stok_akhir' => 20,
        'stok_akhir_sistem' => 20,
        'stok_minimum' => 2,
        'jumlah_diminta' => 0,
    ]);

    $batch = BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'BATCH-LOOKUP-1',
        'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
        'jumlah_awal' => 12,
        'jumlah_sisa' => 12,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    $distribution = Distribution::create([
        'no_surat' => 'SJ-TEST-0001',
        'facility_id' => $facility->id,
        'tanggal' => now()->toDateString(),
        'status' => StatusDistribusi::Dikirim,
        'user_id' => $user->id,
    ]);
    $distribution->items()->create([
        'obat_id' => $obat->id,
        'batch_id' => $batch->id,
        'jumlah' => 12,
        'harga_satuan' => 1000,
        'subtotal' => 12000,
    ]);

    $this->actingAs($user)
        ->getJson(route('lplpo.item-lookup', [
            'obat_id' => $obat->id,
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]))
        ->assertOk()
        ->assertJson([
            'stok_awal' => 20,
            'pemasukan' => 12,
            'pemakaian' => 0,
            'stok_akhir' => 32,
        ]);
});

test('lplpo list can be filtered by search, status, period, and facility', function () {
    $facilityA = lplpoPageFacility();
    $facilityB = lplpoPageFacility();
    $first = lplpoPageRecord(StatusLplpo::Draft, $facilityA);
    $second = lplpoPageRecord(StatusLplpo::Diverifikasi, $facilityB);

    $user = lplpoPageUser('apoteker');

    $this->actingAs($user)
        ->get(route('lplpo.index', ['search' => $first->no_lplpo]))
        ->assertOk()
        ->assertSee($first->no_lplpo)
        ->assertDontSee($second->no_lplpo);

    $this->actingAs($user)
        ->get(route('lplpo.index', ['status' => StatusLplpo::Diverifikasi->value]))
        ->assertOk()
        ->assertSee($second->no_lplpo)
        ->assertDontSee($first->no_lplpo);

    $this->actingAs($user)
        ->get(route('lplpo.index', ['facility' => $facilityA->id]))
        ->assertOk()
        ->assertSee($first->no_lplpo)
        ->assertDontSee($second->no_lplpo);

    $this->actingAs($user)
        ->get(route('lplpo.index', ['bulan' => 2, 'tahun' => now()->year]))
        ->assertOk()
        ->assertDontSee($first->no_lplpo)
        ->assertDontSee($second->no_lplpo);
});

test('lplpo list returns a partial fragment for ajax filtering', function () {
    $lplpo = lplpoPageRecord();

    $response = $this->actingAs(lplpoPageUser('apoteker'))
        ->get(route('lplpo.index', ['partial' => 1]))
        ->assertOk();

    expect($response->getContent())
        ->toContain($lplpo->no_lplpo)
        ->not->toContain('<!doctype html>');
});

test('users without create-lplpo permission cannot create lplpo', function () {
    $facility = lplpoPageFacility();

    $this->actingAs(lplpoPageUser('distribusi', $facility))
        ->get(route('lplpo.create'))
        ->assertForbidden();
});
