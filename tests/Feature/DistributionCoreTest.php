<?php

use App\Actions\Distribusi\ApproveDistribution;
use App\Actions\Distribusi\CancelDistribution;
use App\Actions\Distribusi\ConfirmDistribution;
use App\Actions\Distribusi\CreateDistribution;
use App\Actions\Distribusi\DistributeDrugs;
use App\Actions\Distribusi\SelectBatchFefo;
use App\Actions\Permintaan\FulfillPermintaan;
use App\Enums\StatusDistribusi;
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
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function distributionBatch(Obat $obat, int $stok, string $expiry, float $harga = 1000): BatchObat
{
    return BatchObat::create([
        'obat_id' => $obat->id,
        'no_batch' => 'BATCH-'.uniqid(),
        'tanggal_kedaluwarsa' => $expiry,
        'jumlah_awal' => $stok,
        'jumlah_sisa' => $stok,
        'harga_beli' => $harga,
        'aktif' => true,
    ]);
}

/**
 * @param  array<int, int>  $obatQty  keyed by obat id
 */
function approvedDistributionPermintaan(array $obatQty): PermintaanObat
{
    $facility = Facility::factory()->create();

    $permintaan = PermintaanObat::create([
        'no_permintaan' => 'PMSK-'.uniqid(),
        'facility_id' => $facility->id,
        'sumber' => SumberPermintaan::Mandiri,
        'tanggal' => now()->toDateString(),
        'status' => StatusPermintaan::Disetujui,
    ]);

    foreach ($obatQty as $obatId => $qty) {
        $permintaan->items()->create([
            'obat_id' => $obatId,
            'jumlah_diminta' => $qty,
            'jumlah_disetujui' => $qty,
            'status_item' => StatusItemPermintaan::Disetujui,
        ]);
    }

    return $permintaan->load('items');
}

test('fefo selects nearest expiry and splits across batches', function () {
    $obat = Obat::factory()->create();
    $near = distributionBatch($obat, 10, now()->addMonths(3)->toDateString());
    $far = distributionBatch($obat, 100, now()->addYears(2)->toDateString());
    distributionBatch($obat, 99, now()->subDay()->toDateString());

    $allocations = app(SelectBatchFefo::class)->handle($obat->id, 30);

    expect($allocations)->toHaveCount(2)
        ->and($allocations[0]['batch']->id)->toBe($near->id)
        ->and($allocations[0]['jumlah'])->toBe(10)
        ->and($allocations[1]['batch']->id)->toBe($far->id)
        ->and($allocations[1]['jumlah'])->toBe(20);
});

test('fefo rejects when stock is insufficient', function () {
    $obat = Obat::factory()->create();
    distributionBatch($obat, 5, now()->addYear()->toDateString());

    expect(fn () => app(SelectBatchFefo::class)->handle($obat->id, 10))
        ->toThrow(ValidationException::class);
});

test('fulfilling permintaan creates draft distribution with fefo snapshot', function () {
    $obat = Obat::factory()->create();
    $near = distributionBatch($obat, 10, now()->addMonths(3)->toDateString(), 1000);
    distributionBatch($obat, 100, now()->addYears(2)->toDateString(), 1200);
    $permintaan = approvedDistributionPermintaan([$obat->id => 30]);

    $distribution = app(FulfillPermintaan::class)->handle($permintaan);

    expect($distribution->status)->toBe(StatusDistribusi::Draft)
        ->and($distribution->permintaan_id)->toBe($permintaan->id)
        ->and($distribution->facility_id)->toBe($permintaan->facility_id)
        ->and($distribution->items)->toHaveCount(2)
        ->and($permintaan->refresh()->status)->toBe(StatusPermintaan::Diproses);

    $first = $distribution->items->firstWhere('batch_id', $near->id);

    expect((float) $first->harga_satuan)->toBe(1000.0)
        ->and((float) $first->subtotal)->toBe(10000.0)
        ->and($first->permintaan_item_id)->toBe($permintaan->items->first()->id);
});

test('sending distribution writes ledger and reduces stock', function () {
    $obat = Obat::factory()->create();
    $batch = distributionBatch($obat, 50, now()->addYear()->toDateString());
    $permintaan = approvedDistributionPermintaan([$obat->id => 20]);
    $distribution = app(FulfillPermintaan::class)->handle($permintaan);

    expect(fn () => app(DistributeDrugs::class)->handle($distribution))
        ->toThrow(ValidationException::class);

    app(ApproveDistribution::class)->handle($distribution);
    $sent = app(DistributeDrugs::class)->handle($distribution->refresh());

    expect($sent->status)->toBe(StatusDistribusi::Dikirim)
        ->and($batch->refresh()->jumlah_sisa)->toBe(30)
        ->and($batch->ledger()->count())->toBe(1);

    $again = app(DistributeDrugs::class)->handle($sent);

    expect($again->status)->toBe(StatusDistribusi::Dikirim)
        ->and($batch->refresh()->jumlah_sisa)->toBe(30)
        ->and($batch->ledger()->count())->toBe(1);
});

test('confirming distribution completes permintaan', function () {
    $obat = Obat::factory()->create();
    distributionBatch($obat, 50, now()->addYear()->toDateString());
    $permintaan = approvedDistributionPermintaan([$obat->id => 20]);

    $distribution = app(FulfillPermintaan::class)->handle($permintaan);
    app(ApproveDistribution::class)->handle($distribution);
    app(DistributeDrugs::class)->handle($distribution->refresh());

    $confirmed = app(ConfirmDistribution::class)->handle($distribution->refresh(), 'Petugas PKM');

    expect($confirmed->status)->toBe(StatusDistribusi::Diterima)
        ->and($confirmed->penerima_nama)->toBe('Petugas PKM')
        ->and($permintaan->refresh()->status)->toBe(StatusPermintaan::Selesai)
        ->and($permintaan->items()->first()->status_item)->toBe(StatusItemPermintaan::Selesai);
});

test('partial distributions track remaining approved quantity', function () {
    $obat = Obat::factory()->create();
    distributionBatch($obat, 100, now()->addYear()->toDateString());
    $permintaan = approvedDistributionPermintaan([$obat->id => 30]);
    $item = $permintaan->items->first();

    app(CreateDistribution::class)->handle(
        (int) $permintaan->facility_id,
        [['obat_id' => $obat->id, 'jumlah' => 10, 'permintaan_item_id' => $item->id]],
        $permintaan->id,
    );

    $remaining = app(FulfillPermintaan::class)->handle($permintaan->refresh());

    expect($remaining->items->sum('jumlah'))->toBe(20)
        ->and($permintaan->refresh()->status)->toBe(StatusPermintaan::Diproses);
});

test('cancelling a draft distribution releases reserved stock', function () {
    $obat = Obat::factory()->create();
    distributionBatch($obat, 100, now()->addYear()->toDateString());
    $permintaan = approvedDistributionPermintaan([$obat->id => 30]);

    $distribution = app(FulfillPermintaan::class)->handle($permintaan);

    app(CancelDistribution::class)->handle($distribution);

    expect($permintaan->refresh()->status)->toBe(StatusPermintaan::Disetujui);

    $again = app(FulfillPermintaan::class)->handle($permintaan->refresh());

    expect($again->items->sum('jumlah'))->toBe(30);
});

test('distribution page renders for authorized role', function () {
    $user = User::factory()->create();
    $user->assignRole('gudang');

    $this->actingAs($user)->get('/distribusi')->assertOk()->assertSee('Distribusi');
});

test('distribution detail renders flow and items', function () {
    $obat = Obat::factory()->create();
    distributionBatch($obat, 50, now()->addYear()->toDateString());
    $permintaan = approvedDistributionPermintaan([$obat->id => 10]);
    $distribution = app(FulfillPermintaan::class)->handle($permintaan);

    $user = User::factory()->create();
    $user->assignRole('apoteker');

    $this->actingAs($user)
        ->get(route('distribusi.show', $distribution))
        ->assertOk()
        ->assertSee($distribution->no_surat)
        ->assertSee($obat->nama);
});
