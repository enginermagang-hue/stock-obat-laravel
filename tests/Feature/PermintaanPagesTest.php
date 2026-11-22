<?php

use App\Enums\StatusPermintaan;
use App\Enums\SumberPermintaan;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Models\User;
use App\Scopes\FacilityScope;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function permintaanPageUser(string $role, ?Facility $facility = null): User
{
    $user = User::factory()->create(['facility_id' => $facility?->id]);
    $user->assignRole($role);

    return $user;
}

function permintaanPageFacility(): Facility
{
    return Facility::factory()->create(['jenis' => 'puskesmas']);
}

function permintaanPageRecord(
    StatusPermintaan $status = StatusPermintaan::Draft,
    ?Facility $facility = null,
    ?string $periodeAwal = null,
    ?string $periodeAkhir = null,
    SumberPermintaan $sumber = SumberPermintaan::Mandiri,
): PermintaanObat {
    $facility ??= permintaanPageFacility();
    $obat = Obat::factory()->create();

    $permintaan = PermintaanObat::create([
        'no_permintaan' => 'PMSK-TEST-'.fake()->unique()->numerify('####'),
        'facility_id' => $facility->id,
        'sumber' => $sumber,
        'periode_awal' => $periodeAwal,
        'periode_akhir' => $periodeAkhir,
        'tanggal' => now()->toDateString(),
        'status' => $status,
    ]);

    $permintaan->items()->create([
        'obat_id' => $obat->id,
        'jumlah_diminta' => 10,
        'keterangan' => 'Item uji',
    ]);

    return $permintaan;
}

test('permintaan index and create pages render', function () {
    $facility = permintaanPageFacility();
    $permintaan = permintaanPageRecord(facility: $facility);

    $this->actingAs(permintaanPageUser('puskesmas', $facility))
        ->get(route('permintaan.index'))
        ->assertOk()
        ->assertSee('Permintaan Obat')
        ->assertSee($permintaan->no_permintaan);

    $this->actingAs(permintaanPageUser('puskesmas', $facility))
        ->get(route('permintaan.create'))
        ->assertOk()
        ->assertSee('Permintaan Mandiri');
});

test('creating a permintaan redirects to its detail page', function () {
    $facility = permintaanPageFacility();
    $obat = Obat::factory()->create();
    $user = permintaanPageUser('puskesmas', $facility);

    $response = $this->actingAs($user)->post(route('permintaan.store'), [
        'facility_id' => $facility->id,
        'periode_awal' => '2026-01-01',
        'periode_akhir' => '2026-01-31',
        'items' => [[
            'obat_id' => $obat->id,
            'jumlah_diminta' => 7,
            'keterangan' => 'Darurat',
        ]],
    ]);

    $permintaan = PermintaanObat::withoutGlobalScope(FacilityScope::class)->firstOrFail();

    $response->assertRedirect(route('permintaan.show', $permintaan));
    expect($permintaan->items()->count())->toBe(1)
        ->and($permintaan->items()->first()->jumlah_diminta)->toBe(7);
});

test('detail page shows items and status', function () {
    $permintaan = permintaanPageRecord();

    $this->actingAs(permintaanPageUser('apoteker'))
        ->get(route('permintaan.show', $permintaan))
        ->assertOk()
        ->assertSee($permintaan->no_permintaan)
        ->assertSee('Item permintaan')
        ->assertSee($permintaan->items->first()->obat->nama);
});

test('draft permintaan can be edited', function () {
    $facility = permintaanPageFacility();
    $permintaan = permintaanPageRecord(facility: $facility);

    $this->actingAs(permintaanPageUser('puskesmas', $facility))
        ->get(route('permintaan.edit', $permintaan))
        ->assertOk()
        ->assertSee('Ubah Permintaan '.$permintaan->no_permintaan);
});

test('non draft permintaan cannot be edited', function () {
    $facility = permintaanPageFacility();
    $permintaan = permintaanPageRecord(StatusPermintaan::Dikirim, $facility);

    $this->actingAs(permintaanPageUser('puskesmas', $facility))
        ->get(route('permintaan.edit', $permintaan))
        ->assertForbidden();
});

test('updating a draft permintaan replaces its items', function () {
    $facility = permintaanPageFacility();
    $permintaan = permintaanPageRecord(facility: $facility);
    $firstObat = $permintaan->items()->first()->obat_id;
    $secondObat = Obat::factory()->create();

    $this->actingAs(permintaanPageUser('puskesmas', $facility))
        ->put(route('permintaan.update', $permintaan), [
            'facility_id' => $facility->id,
            'periode_awal' => '2026-02-01',
            'periode_akhir' => '2026-02-28',
            'items' => [
                ['obat_id' => $firstObat, 'jumlah_diminta' => 5],
                ['obat_id' => $secondObat->id, 'jumlah_diminta' => 8, 'keterangan' => 'Tambahan'],
            ],
        ])
        ->assertRedirect(route('permintaan.show', $permintaan));

    $permintaan->refresh();

    expect($permintaan->periode_awal->toDateString())->toBe('2026-02-01')
        ->and($permintaan->items()->count())->toBe(2)
        ->and($permintaan->items()->where('obat_id', $secondObat->id)->exists())->toBeTrue();
});

test('permintaan list can be filtered by search, status, source, facility, and period', function () {
    $facilityA = permintaanPageFacility();
    $facilityB = permintaanPageFacility();
    $first = permintaanPageRecord(
        StatusPermintaan::Draft,
        $facilityA,
        '2026-01-01',
        '2026-01-31',
        SumberPermintaan::Mandiri,
    );
    $second = permintaanPageRecord(
        StatusPermintaan::Dikirim,
        $facilityB,
        '2026-03-01',
        '2026-03-31',
        SumberPermintaan::Lplpo,
    );

    $user = permintaanPageUser('apoteker');

    $this->actingAs($user)
        ->get(route('permintaan.index', ['search' => $first->no_permintaan]))
        ->assertOk()
        ->assertSee($first->no_permintaan)
        ->assertDontSee($second->no_permintaan);

    $this->actingAs($user)
        ->get(route('permintaan.index', ['status' => StatusPermintaan::Dikirim->value]))
        ->assertOk()
        ->assertSee($second->no_permintaan)
        ->assertDontSee($first->no_permintaan);

    $this->actingAs($user)
        ->get(route('permintaan.index', ['sumber' => SumberPermintaan::Lplpo->value]))
        ->assertOk()
        ->assertSee($second->no_permintaan)
        ->assertDontSee($first->no_permintaan);

    $this->actingAs($user)
        ->get(route('permintaan.index', ['facility' => $facilityA->id]))
        ->assertOk()
        ->assertSee($first->no_permintaan)
        ->assertDontSee($second->no_permintaan);

    $this->actingAs($user)
        ->get(route('permintaan.index', ['from' => '2026-02-01', 'to' => '2026-02-28']))
        ->assertOk()
        ->assertDontSee($first->no_permintaan)
        ->assertDontSee($second->no_permintaan);

    $this->actingAs($user)
        ->get(route('permintaan.index', ['from' => '2026-02-01']))
        ->assertOk()
        ->assertSee($second->no_permintaan)
        ->assertDontSee($first->no_permintaan);
});

test('apoteker sees verification inputs for a submitted permintaan', function () {
    $permintaan = permintaanPageRecord(StatusPermintaan::Dikirim);

    $this->actingAs(permintaanPageUser('apoteker'))
        ->get(route('permintaan.show', $permintaan))
        ->assertOk()
        ->assertSee('Simpan Verifikasi')
        ->assertSee('catatan_verifikasi', false);
});

test('verifying a permintaan redirects to its detail page', function () {
    $permintaan = permintaanPageRecord(StatusPermintaan::Dikirim);
    $item = $permintaan->items->first();

    $this->actingAs(permintaanPageUser('apoteker'))
        ->post(route('permintaan.verify', $permintaan), [
            'decisions' => [$item->id => ['jumlah_disetujui' => $item->jumlah_diminta]],
            'catatan_verifikasi' => 'Disetujui penuh',
        ])
        ->assertRedirect(route('permintaan.show', $permintaan));

    expect($permintaan->refresh()->status)->toBe(StatusPermintaan::Disetujui);
});

test('permintaan list returns a partial fragment for ajax filtering', function () {
    $permintaan = permintaanPageRecord();

    $response = $this->actingAs(permintaanPageUser('apoteker'))
        ->get(route('permintaan.index', ['partial' => 1]))
        ->assertOk();

    expect($response->getContent())
        ->toContain($permintaan->no_permintaan)
        ->not->toContain('<!doctype html>');
});

test('users without create-permintaan permission cannot create permintaan', function () {
    $facility = permintaanPageFacility();

    $this->actingAs(permintaanPageUser('distribusi', $facility))
        ->get(route('permintaan.create'))
        ->assertForbidden();
});
