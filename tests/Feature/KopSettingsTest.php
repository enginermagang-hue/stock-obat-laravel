<?php

use App\Enums\StatusDistribusi;
use App\Models\Distribution;
use App\Models\Facility;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function kopUser(string $role, ?Facility $facility = null): User
{
    $user = User::factory()->create(['facility_id' => $facility?->id]);
    $user->assignRole($role);

    return $user;
}

test('admin can update kop text and upload dinas logo', function () {
    Storage::fake('local');

    $this->actingAs(kopUser('admin'))
        ->put(route('pengaturan.kop.update'), [
            'line1' => 'PEMERINTAH KABUPATEN KUPANG',
            'line2' => 'DINAS KESEHATAN KABUPATEN KUPANG',
            'line3' => 'Jl. Timor Raya KM 36, Oelamasi, Kabupaten Kupang',
            'logo' => UploadedFile::fake()->image('dinas.png', 200, 200),
        ])
        ->assertRedirect();

    expect(Setting::get('kop_line2'))->toBe('DINAS KESEHATAN KABUPATEN KUPANG')
        ->and(Setting::get('kop_logo_path'))->not->toBeNull();

    Storage::disk('local')->assertExists(Setting::get('kop_logo_path'));
});

test('non admin cannot open kop settings', function () {
    $this->actingAs(kopUser('puskesmas', Facility::factory()->create(['jenis' => 'puskesmas'])))
        ->get(route('pengaturan.kop'))
        ->assertForbidden();
});

test('puskesmas user can upload logo for its own facility', function () {
    Storage::fake('local');

    $facility = Facility::factory()->create(['jenis' => 'puskesmas']);
    $user = kopUser('puskesmas', $facility);

    $this->actingAs($user)
        ->put(route('facility.logo.update'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])
        ->assertRedirect();

    $facility->refresh();

    expect($facility->logo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($facility->logo_path);

    $this->actingAs($user)
        ->get(route('facility.logo.preview'))
        ->assertOk();
});

test('user without facility cannot access facility logo page', function () {
    $this->actingAs(kopUser('apoteker'))
        ->get(route('facility.logo'))
        ->assertForbidden();
});

test('distribution documents render kop lines and both logos', function () {
    Storage::fake('local');

    $dinasLogo = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z0YQAAAAASUVORK5CYII=');
    $facilityLogo = $dinasLogo;

    Storage::disk('local')->put('kop/dinas.png', $dinasLogo);
    Storage::disk('local')->put('logo-facility/pkm.png', $facilityLogo);

    Setting::set('kop_logo_path', 'kop/dinas.png');

    $facility = Facility::factory()->create([
        'jenis' => 'puskesmas',
        'logo_path' => 'logo-facility/pkm.png',
    ]);

    $distribution = Distribution::create([
        'no_surat' => 'SJ-KOP-0001',
        'no_sbbk' => 'SBBK-KOP-0001',
        'no_bast' => 'BAST-KOP-0001',
        'facility_id' => $facility->id,
        'tanggal' => '2026-01-10',
        'tanggal_keluar' => '2026-01-10',
        'tanggal_diterima' => '2026-01-11',
        'status' => StatusDistribusi::Diterima,
    ]);
    $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch', 'pengirim', 'approver']);

    $html = view('pdf.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'semua'])->render();

    expect($html)
        ->toContain('PEMERINTAH KABUPATEN KUPANG')
        ->toContain('DINAS KESEHATAN KABUPATEN KUPANG')
        ->toContain('Jl. Timor Raya KM 36, Oelamasi, Kabupaten Kupang')
        ->toContain('data:image/png;base64,');
});
