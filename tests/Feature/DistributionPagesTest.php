<?php

use App\Actions\Distribusi\ApproveDistribution;
use App\Actions\Distribusi\ConfirmDistribution;
use App\Actions\Distribusi\DistributeDrugs;
use App\Enums\StatusDistribusi;
use App\Enums\StatusItemPermintaan;
use App\Enums\StatusPermintaan;
use App\Enums\SumberPermintaan;
use App\Models\BatchObat;
use App\Models\Distribution;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\PermintaanObat;
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

function distribusiPageUser(string $role, ?Facility $facility = null): User
{
    $user = User::factory()->create(['facility_id' => $facility?->id]);
    $user->assignRole($role);

    return $user;
}

function distribusiPageFacility(): Facility
{
    return Facility::factory()->create(['jenis' => 'puskesmas']);
}

function distribusiPageRecord(
    StatusDistribusi $status = StatusDistribusi::Draft,
    ?Facility $facility = null,
    string $tanggal = '2026-01-10',
): Distribution {
    $facility ??= distribusiPageFacility();
    $obat = Obat::factory()->create();
    $batch = BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'BATCH-PAGE-'.fake()->unique()->numerify('####'),
        'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
        'jumlah_awal' => 50,
        'jumlah_sisa' => 50,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    $distribution = Distribution::create([
        'no_surat' => 'SJ-TEST-'.fake()->unique()->numerify('####'),
        'facility_id' => $facility->id,
        'tanggal' => $tanggal,
        'status' => $status,
    ]);

    $distribution->items()->create([
        'obat_id' => $obat->id,
        'batch_id' => $batch->id,
        'jumlah' => 5,
        'harga_satuan' => 1000,
        'subtotal' => 5000,
    ]);

    return $distribution;
}

function distribusiPagePermintaan(int $qty = 10): PermintaanObat
{
    $facility = distribusiPageFacility();
    $obat = Obat::factory()->create();

    BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'BATCH-STORE-'.fake()->unique()->numerify('####'),
        'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
        'jumlah_awal' => $qty * 2,
        'jumlah_sisa' => $qty * 2,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    $permintaan = PermintaanObat::create([
        'no_permintaan' => 'PMSK-PAGE-'.fake()->unique()->numerify('####'),
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

    return $permintaan;
}

test('distribution index and create pages render', function () {
    $distribution = distribusiPageRecord();

    $this->actingAs(distribusiPageUser('gudang'))
        ->get(route('distribusi.index'))
        ->assertOk()
        ->assertSee('Distribusi')
        ->assertSee($distribution->no_surat);

    $this->actingAs(distribusiPageUser('gudang'))
        ->get(route('distribusi.create'))
        ->assertOk()
        ->assertSee('Buat Surat Jalan');
});

test('creating a distribution redirects to its detail page', function () {
    $permintaan = distribusiPagePermintaan(10);

    $response = $this->actingAs(distribusiPageUser('gudang'))->post(route('distribusi.store'), [
        'permintaan_id' => $permintaan->id,
        'tanggal' => '2026-01-15',
        'catatan' => 'Kirim pagi',
    ]);

    $distribution = Distribution::firstOrFail();

    $response->assertRedirect(route('distribusi.show', $distribution));
    expect($distribution->status)->toBe(StatusDistribusi::Draft)
        ->and($distribution->items()->count())->toBe(1)
        ->and($distribution->catatan)->toBe('Kirim pagi');
});

test('detail page shows items, flow, and status', function () {
    $distribution = distribusiPageRecord(StatusDistribusi::Disetujui);

    $this->actingAs(distribusiPageUser('apoteker'))
        ->get(route('distribusi.show', $distribution))
        ->assertOk()
        ->assertSee($distribution->no_surat)
        ->assertSee('Item surat jalan')
        ->assertSee($distribution->items->first()->obat->nama);
});

test('draft distribution can be edited', function () {
    $distribution = distribusiPageRecord();

    $this->actingAs(distribusiPageUser('gudang'))
        ->get(route('distribusi.edit', $distribution))
        ->assertOk()
        ->assertSee('Ubah Surat Jalan '.$distribution->no_surat);
});

test('non draft distribution cannot be edited', function () {
    $distribution = distribusiPageRecord(StatusDistribusi::Dikirim);

    $this->actingAs(distribusiPageUser('gudang'))
        ->get(route('distribusi.edit', $distribution))
        ->assertForbidden();
});

test('updating a draft distribution changes its header', function () {
    $distribution = distribusiPageRecord();

    $this->actingAs(distribusiPageUser('gudang'))
        ->put(route('distribusi.update', $distribution), [
            'tanggal' => '2026-02-02',
            'catatan' => 'Revisi catatan',
        ])
        ->assertRedirect(route('distribusi.show', $distribution));

    $distribution->refresh();

    expect($distribution->tanggal->toDateString())->toBe('2026-02-02')
        ->and($distribution->catatan)->toBe('Revisi catatan');
});

test('distribution list can be filtered by search, status, facility, and date', function () {
    $facilityA = distribusiPageFacility();
    $facilityB = distribusiPageFacility();
    $first = distribusiPageRecord(StatusDistribusi::Draft, $facilityA, '2026-01-10');
    $second = distribusiPageRecord(StatusDistribusi::Dikirim, $facilityB, '2026-03-15');

    $user = distribusiPageUser('apoteker');

    $this->actingAs($user)
        ->get(route('distribusi.index', ['search' => $first->no_surat]))
        ->assertOk()
        ->assertSee($first->no_surat)
        ->assertDontSee($second->no_surat);

    $this->actingAs($user)
        ->get(route('distribusi.index', ['status' => StatusDistribusi::Dikirim->value]))
        ->assertOk()
        ->assertSee($second->no_surat)
        ->assertDontSee($first->no_surat);

    $this->actingAs($user)
        ->get(route('distribusi.index', ['facility' => $facilityA->id]))
        ->assertOk()
        ->assertSee($first->no_surat)
        ->assertDontSee($second->no_surat);

    $this->actingAs($user)
        ->get(route('distribusi.index', ['from' => '2026-02-01']))
        ->assertOk()
        ->assertSee($second->no_surat)
        ->assertDontSee($first->no_surat);

    $this->actingAs($user)
        ->get(route('distribusi.index', ['to' => '2026-02-28']))
        ->assertOk()
        ->assertSee($first->no_surat)
        ->assertDontSee($second->no_surat);
});

test('preview shows remaining quantity and fefo batch allocation', function () {
    $permintaan = distribusiPagePermintaan(10);

    $this->actingAs(distribusiPageUser('gudang'))
        ->getJson(route('distribusi.preview', ['permintaan_id' => $permintaan->id]))
        ->assertOk()
        ->assertJsonPath('no_permintaan', $permintaan->no_permintaan)
        ->assertJsonPath('items.0.disetujui', 10)
        ->assertJsonPath('items.0.terpenuhi', 0)
        ->assertJsonPath('items.0.sisa', 10)
        ->assertJsonPath('items.0.kekurangan', 0)
        ->assertJsonCount(1, 'items.0.batches')
        ->assertJsonPath('items.0.batches.0.jumlah', 10);
});

test('preview reports stock shortage without failing', function () {
    $facility = distribusiPageFacility();
    $obat = Obat::factory()->create();

    BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'BATCH-SHORT-'.fake()->unique()->numerify('####'),
        'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
        'jumlah_awal' => 4,
        'jumlah_sisa' => 4,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    $permintaan = PermintaanObat::create([
        'no_permintaan' => 'PMSK-SHORT-'.fake()->unique()->numerify('####'),
        'facility_id' => $facility->id,
        'sumber' => SumberPermintaan::Mandiri,
        'tanggal' => now()->toDateString(),
        'status' => StatusPermintaan::Disetujui,
    ]);
    $permintaan->items()->create([
        'obat_id' => $obat->id,
        'jumlah_diminta' => 10,
        'jumlah_disetujui' => 10,
        'status_item' => StatusItemPermintaan::Disetujui,
    ]);

    $response = $this->actingAs(distribusiPageUser('gudang'))
        ->getJson(route('distribusi.preview', ['permintaan_id' => $permintaan->id]))
        ->assertOk();

    expect($response->json('items.0.kekurangan'))->toBe(6)
        ->and($response->json('items.0.batches.0.jumlah'))->toBe(4)
        ->and($response->json('warnings'))->not->toBeEmpty();
});

test('preview rejects a permintaan that is not approved', function () {
    $facility = distribusiPageFacility();
    $permintaan = PermintaanObat::create([
        'no_permintaan' => 'PMSK-DRAFT-'.fake()->unique()->numerify('####'),
        'facility_id' => $facility->id,
        'sumber' => SumberPermintaan::Mandiri,
        'tanggal' => now()->toDateString(),
        'status' => StatusPermintaan::Draft,
    ]);

    $this->actingAs(distribusiPageUser('gudang'))
        ->getJson(route('distribusi.preview', ['permintaan_id' => $permintaan->id]))
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

test('users without create-distribution permission cannot preview distribution', function () {
    $permintaan = distribusiPagePermintaan(5);

    $this->actingAs(distribusiPageUser('puskesmas'))
        ->getJson(route('distribusi.preview', ['permintaan_id' => $permintaan->id]))
        ->assertForbidden();
});

test('sending a distribution issues an sbbk number and confirming issues a bast number', function () {
    $distribution = distribusiPageRecord();

    app(ApproveDistribution::class)->handle($distribution);

    $sent = app(DistributeDrugs::class)->handle($distribution->refresh());

    expect($sent->no_sbbk)->toStartWith('SBBK-'.now()->year.'-')
        ->and($sent->tanggal_keluar?->toDateString())->toBe(now()->toDateString());

    $confirmed = app(ConfirmDistribution::class)->handle($sent->refresh(), 'Petugas PKM');

    expect($confirmed->no_bast)->toStartWith('BAST-'.now()->year.'-')
        ->and($confirmed->penerima_nama)->toBe('Petugas PKM');
});

test('surat jalan document includes pharmaceutical attributes and document numbers', function () {
    $distribution = distribusiPageRecord();
    $obat = $distribution->items->first()->obat;

    $distribution->update([
        'no_sbbk' => 'SBBK-2026-0001',
        'tanggal_keluar' => '2026-01-11',
        'no_bast' => 'BAST-2026-0001',
    ]);

    $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch', 'pengirim', 'approver']);

    $html = view('pdf.surat-jalan', ['distribution' => $distribution])->render();

    expect($html)
        ->toContain('SBBK-2026-0001')
        ->toContain('BAST-2026-0001')
        ->toContain($obat->bentuk_sediaan->value)
        ->toContain($obat->satuan)
        ->toContain('Apoteker Penanggung Jawab');
});

test('confirming a distribution stores an optional signed document', function () {
    Storage::fake('local');

    $distribution = distribusiPageRecord(StatusDistribusi::Dikirim);
    $user = distribusiPageUser('puskesmas', $distribution->facility);

    $this->actingAs($user)
        ->post(route('distribusi.confirm', $distribution), [
            'penerima_nama' => 'Petugas PKM',
            'file_tanda_terima' => UploadedFile::fake()->create('bast.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect(route('distribusi.show', $distribution));

    $distribution->refresh();

    expect($distribution->no_bast)->toStartWith('BAST-')
        ->and($distribution->file_tanda_terima)->not->toBeNull();

    Storage::disk('local')->assertExists($distribution->file_tanda_terima);

    $this->actingAs($user)
        ->get(route('distribusi.tanda-terima', $distribution))
        ->assertOk();
});

test('each distribution document can be rendered separately', function () {
    $distribution = distribusiPageRecord();

    $distribution->update([
        'no_sbbk' => 'SBBK-2026-0002',
        'tanggal_keluar' => '2026-01-11',
        'no_bast' => 'BAST-2026-0002',
        'tanggal_diterima' => '2026-01-12',
    ]);

    $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch', 'pengirim', 'approver']);

    $suratJalan = view('pdf.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'surat-jalan'])->render();
    $sbbk = view('pdf.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'sbbk'])->render();
    $bast = view('pdf.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'bast'])->render();

    expect($suratJalan)
        ->toContain('<h1>SURAT JALAN</h1>')
        ->not->toContain('BERITA ACARA SERAH TERIMA');

    expect($sbbk)
        ->toContain('SURAT BUKTI BARANG KELUAR')
        ->toContain('SBBK-2026-0002')
        ->not->toContain('BERITA ACARA SERAH TERIMA');

    expect($bast)
        ->toContain('BERITA ACARA SERAH TERIMA')
        ->toContain('BAST-2026-0002')
        ->not->toContain('SURAT BUKTI BARANG KELUAR');
});

test('distribution documents can be downloaded separately', function () {
    $distribution = distribusiPageRecord();
    $user = distribusiPageUser('apoteker');

    $this->actingAs($user)
        ->get(route('distribusi.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'sbbk']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($user)
        ->get(route('distribusi.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'tidak-ada']))
        ->assertNotFound();
});

test('bast document follows the reference format', function () {
    $distribution = distribusiPageRecord();

    $distribution->update([
        'no_bast' => 'BAST-2026-0100',
        'tanggal_diterima' => '2026-01-12',
        'penerima_nama' => 'Petugas PKM',
        'penerima_nip' => '198001012005011001',
        'penerima_jabatan' => 'Kepala Puskesmas',
        'penerima_unit' => 'Puskesmas Uji',
    ]);

    $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch', 'pengirim', 'approver']);

    $html = view('pdf.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'bast'])->render();

    expect($html)
        ->toContain('Nomor : BAST-2026-0100')
        ->toContain('Pada hari ini Senin tanggal 12 bulan Januari, tahun 2026')
        ->toContain('Demikian Berita Acara ini dibuat untuk dipergunakan sebagaimana mestinya.')
        ->toContain('<th>Nama obat</th>')
        ->toContain('<th>Satuan</th>')
        ->toContain('NIP. 198001012005011001')
        ->toContain('Yang menyerahkan')
        ->not->toContain('SURAT BUKTI BARANG KELUAR');
});

test('confirming stores recipient identity snapshot and keeps it immutable', function () {
    $facility = distribusiPageFacility();
    $distribution = distribusiPageRecord(StatusDistribusi::Dikirim, $facility);
    $user = distribusiPageUser('puskesmas', $facility);
    $user->update(['nip' => '111', 'jabatan' => 'Apoteker']);

    $this->actingAs($user)
        ->post(route('distribusi.confirm', $distribution), [
            'penerima_nama' => 'Petugas PKM',
            'file_tanda_terima' => UploadedFile::fake()->image('bast.png', 400, 300),
        ])
        ->assertRedirect(route('distribusi.show', $distribution));

    $distribution->refresh();

    expect($distribution->penerima_nip)->toBe('111')
        ->and($distribution->penerima_jabatan)->toBe('Apoteker')
        ->and($distribution->penerima_unit)->toBe($facility->nama);

    $user->update(['nip' => '222', 'jabatan' => 'Direktur']);

    $distribution->load(['facility', 'permintaan', 'items.obat', 'items.batch', 'pengirim', 'approver']);

    $html = view('pdf.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'bast'])->render();

    expect($html)->toContain('NIP. 111')->not->toContain('NIP. 222');
});

test('distribution documents are streamed inline for preview', function () {
    $distribution = distribusiPageRecord();

    $response = $this->actingAs(distribusiPageUser('apoteker'))
        ->get(route('distribusi.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'bast']));

    $response->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('inline');
});

test('confirming requires a signed document file', function () {
    Storage::fake('local');

    $distribution = distribusiPageRecord(StatusDistribusi::Dikirim);

    $this->actingAs(distribusiPageUser('puskesmas', $distribution->facility))
        ->post(route('distribusi.confirm', $distribution), [
            'penerima_nama' => 'Petugas PKM',
        ])
        ->assertSessionHasErrors('file_tanda_terima');
});

test('confirmation modal only appears for sent distributions', function () {
    $facility = distribusiPageFacility();
    $sent = distribusiPageRecord(StatusDistribusi::Dikirim, $facility);
    $received = distribusiPageRecord(StatusDistribusi::Diterima, $facility);

    $this->actingAs(distribusiPageUser('puskesmas', $facility))
        ->get(route('distribusi.show', $sent))
        ->assertOk()
        ->assertSee('Konfirmasi Terima')
        ->assertSee('name="file_tanda_terima"', false)
        ->assertSee('Saya yang menerima');

    $this->actingAs(distribusiPageUser('puskesmas', $facility))
        ->get(route('distribusi.show', $received))
        ->assertOk()
        ->assertDontSee('Konfirmasi Terima');
});

test('distribution list returns a partial fragment for ajax filtering', function () {
    $distribution = distribusiPageRecord();

    $response = $this->actingAs(distribusiPageUser('apoteker'))
        ->get(route('distribusi.index', ['partial' => 1]))
        ->assertOk();

    expect($response->getContent())
        ->toContain($distribution->no_surat)
        ->not->toContain('<!doctype html>');
});

test('users without create-distribution permission cannot create distribution', function () {
    $this->actingAs(distribusiPageUser('puskesmas'))
        ->get(route('distribusi.create'))
        ->assertForbidden();
});
