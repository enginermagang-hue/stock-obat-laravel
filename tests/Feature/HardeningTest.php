<?php

use App\Models\BatchObat;
use App\Models\Facility;
use App\Models\License;
use App\Models\Obat;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\Laporan\ReportBuilder;
use App\Services\Laporan\ReportCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('api routes are rate limited', function () {
    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'api/v1/reports/{report}');

    expect($route)->not->toBeNull()
        ->and($route->middleware())->toContain('throttle:60,1');
});

test('backup disk and schedule are configured', function () {
    expect(config('backup.backup.destination.disks'))->toContain('backup')
        ->and(config('filesystems.disks.backup.root'))->toBe(storage_path('app/backup'));

    Artisan::call('schedule:list');
    $output = Artisan::output();

    expect($output)->toContain('backup:run')
        ->and($output)->toContain('backup:clean')
        ->and($output)->toContain('backup:monitor')
        ->and($output)->toContain('licenses:check-expiry');
});

test('report catalog enforces facility and audit permissions', function () {
    $facility = Facility::factory()->create();

    $puskesmas = User::factory()->create(['facility_id' => $facility->id]);
    $puskesmas->assignRole('puskesmas');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $gudang = User::factory()->create();
    $gudang->assignRole('gudang');

    expect(ReportCatalog::canView($puskesmas, 'stock-summary'))->toBeFalse()
        ->and(ReportCatalog::canView($puskesmas, 'lplpo-recap'))->toBeTrue()
        ->and(ReportCatalog::canView($puskesmas, 'audit-log'))->toBeFalse()
        ->and(ReportCatalog::canView($admin, 'audit-log'))->toBeTrue()
        ->and(ReportCatalog::canView($gudang, 'audit-log'))->toBeFalse()
        ->and(ReportCatalog::canView($admin, 'unknown-report'))->toBeFalse();
});

test('report builder rejects unknown report', function () {
    expect(fn () => app(ReportBuilder::class)->build('unknown-report', []))
        ->toThrow(InvalidArgumentException::class);
});

test('stock ledger remains append only', function () {
    expect(Schema::hasColumn('stock_ledger', 'updated_at'))->toBeFalse()
        ->and((new StockLedger)->timestamps)->toBeFalse();
});

test('low stock report filters in the database', function () {
    $low = Obat::factory()->create(['stok_minimum' => 10]);
    $healthy = Obat::factory()->create(['stok_minimum' => 1]);

    BatchObat::create([
        'obat_id' => $low->id,
        'no_batch' => 'LOW-1',
        'tanggal_kedaluwarsa' => today()->addYear(),
        'jumlah_awal' => 5,
        'jumlah_sisa' => 5,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    BatchObat::create([
        'obat_id' => $healthy->id,
        'no_batch' => 'OK-1',
        'tanggal_kedaluwarsa' => today()->addYear(),
        'jumlah_awal' => 100,
        'jumlah_sisa' => 100,
        'harga_beli' => 1000,
        'aktif' => true,
    ]);

    $report = app(ReportBuilder::class)->build('low-stock', []);

    expect($report['rows'])->toHaveCount(1)
        ->and($report['rows'][0]['obat'])->toBe($low->nama);
});

test('license policy follows master permissions', function () {
    $license = License::factory()->create();

    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');

    $puskesmas = User::factory()->create();
    $puskesmas->assignRole('puskesmas');

    expect($apoteker->can('viewAny', License::class))->toBeTrue()
        ->and($apoteker->can('update', $license))->toBeTrue()
        ->and($puskesmas->can('viewAny', License::class))->toBeFalse()
        ->and($puskesmas->can('update', $license))->toBeFalse();
});
