<?php

use App\Enums\StatusLplpo;
use App\Models\Anggaran;
use App\Models\BatchObat;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Models\SumberDana;
use App\Models\User;
use App\Services\Laporan\ReportBuilder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('every report can be built', function () {
    $builder = app(ReportBuilder::class);

    foreach ($builder->keys() as $key) {
        $report = $builder->build($key, ['tahun' => now()->year]);

        expect($report['headings'])->not->toBeEmpty()
            ->and($report['key'])->toBe($key);
    }
});

test('stock reports summarize batches', function () {
    $obat = Obat::factory()->create(['stok_minimum' => 10]);

    BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'EXP-1',
        'tanggal_kedaluwarsa' => today()->addDays(20),
        'jumlah_awal' => 5,
        'jumlah_sisa' => 5,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'FAR-1',
        'tanggal_kedaluwarsa' => today()->addYears(2),
        'jumlah_awal' => 3,
        'jumlah_sisa' => 3,
        'harga_beli' => 2000,
        'aktif' => true,
    ]);

    $builder = app(ReportBuilder::class);

    $summary = $builder->build('stock-summary', []);
    expect($summary['rows'])->toHaveCount(1)
        ->and($summary['rows'][0]['total_sisa'])->toBe(8)
        ->and((float) $summary['rows'][0]['total_nilai'])->toBe(11000.0);

    $low = $builder->build('low-stock', []);
    expect($low['rows'])->toHaveCount(1)
        ->and($low['rows'][0]['defisit'])->toBe(2);

    $expiry = $builder->build('expiry', ['days' => 30]);
    expect($expiry['rows'])->toHaveCount(1)
        ->and($expiry['rows'][0]['batch'])->toBe('EXP-1')
        ->and($expiry['rows'][0]['status'])->toBe('segera');
});

test('lplpo recap and usage trend aggregate verified periods', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();

    $lplpo = Lplpo::create([
        'no_lplpo' => 'LPLPO-2026-001',
        'facility_id' => $facility->id,
        'bulan' => 11,
        'tahun' => 2026,
        'status' => StatusLplpo::Diverifikasi,
    ]);

    $lplpo->items()->create([
        'obat_id' => $obat->id,
        'pemakaian' => 40,
        'stok_akhir' => 10,
        'stok_akhir_sistem' => 12,
        'jumlah_diminta' => 30,
    ]);

    $builder = app(ReportBuilder::class);

    $recap = $builder->build('lplpo-recap', ['tahun' => 2026]);
    expect($recap['rows'])->toHaveCount(1)
        ->and($recap['rows'][0]['selisih'])->toBe(-2)
        ->and($recap['rows'][0]['periode'])->toBe('11/2026');

    $trend = $builder->build('tren-pemakaian', ['tahun' => 2026, 'obat_id' => $obat->id]);
    expect($trend['rows'])->toHaveCount(1)
        ->and($trend['rows'][0]['pemakaian'])->toBe(40);
});

test('budget report uses pagu and realisation', function () {
    $sumberDana = SumberDana::factory()->create();

    Anggaran::create([
        'sumber_dana_id' => $sumberDana->id,
        'tahun' => 2026,
        'facility_id' => Anggaran::RS_FACILITY_ID,
        'jumlah_anggaran' => 1_000_000,
        'status' => 'disetujui',
        'versi' => 1,
    ]);

    $report = app(ReportBuilder::class)->build('anggaran-realisasi', ['tahun' => 2026]);

    expect($report['rows'])->toHaveCount(2)
        ->and((float) $report['rows'][0]['pagu'])->toBe(1_000_000.0)
        ->and($report['rows'][1]['sumber_dana'])->toBe('Total');
});

test('audit report returns activity rows', function () {
    activity('uji')->log('contoh perubahan');

    $report = app(ReportBuilder::class)->build('audit-log', [
        'from' => today()->toDateString(),
        'to' => today()->toDateString(),
    ]);

    expect($report['rows'])->toHaveCount(1)
        ->and($report['rows'][0]['event'])->toBe('contoh perubahan');
});

test('report page renders for authorized role', function () {
    $user = User::factory()->create();
    $user->assignRole('apoteker');

    $this->actingAs($user)->get('/laporan')->assertOk()->assertSee('Laporan');
});
