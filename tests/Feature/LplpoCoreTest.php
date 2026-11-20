<?php

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Actions\Lplpo\VerifyLplpo;
use App\Actions\Permintaan\CreatePermintaanFromLplpo;
use App\Actions\Permintaan\VerifyPermintaan;
use App\Enums\StatusItemPermintaan;
use App\Enums\StatusPermintaan;
use App\Models\Facility;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function makeLplpo(int $facilityId, int $obatId, int $bulan, int $tahun, int $stokAkhir = 100, int $diminta = 0): Lplpo
{
    return app(GenerateLplpo::class)->handle(
        $facilityId,
        $bulan,
        $tahun,
        [[
            'obat_id' => $obatId,
            'pemasukan' => 50,
            'pemakaian' => 20,
            'stok_akhir' => $stokAkhir,
            'jumlah_diminta' => $diminta,
        ]],
    );
}

test('lplpo carries forward stock from previous verified period', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create(['stok_minimum' => 10]);

    $first = makeLplpo($facility->id, $obat->id, 10, 2026, 120);
    expect($first->versi)->toBe(1)
        ->and($first->items->first()->stok_awal)->toBe(0)
        ->and($first->items->first()->stok_akhir_sistem)->toBe(30);

    app(SubmitLplpo::class)->handle($first);
    app(VerifyLplpo::class)->handle($first->refresh(), true);

    $second = makeLplpo($facility->id, $obat->id, 11, 2026, 130);

    expect($second->versi)->toBe(1)
        ->and($second->items->first()->stok_awal)->toBe(120)
        ->and($second->items->first()->stok_akhir_sistem)->toBe(150);
});

test('new lplpo version is created after verification', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();

    $first = makeLplpo($facility->id, $obat->id, 11, 2026, 50);
    app(SubmitLplpo::class)->handle($first);
    app(VerifyLplpo::class)->handle($first->refresh(), true);

    $second = makeLplpo($facility->id, $obat->id, 11, 2026, 55);

    expect($second->id)->not->toBe($first->id)
        ->and($second->versi)->toBe(2);
});

test('lplpo facility scope hides other facilities', function () {
    $a = Facility::factory()->create();
    $b = Facility::factory()->create();
    $obat = Obat::factory()->create();

    makeLplpo($a->id, $obat->id, 11, 2026);

    $user = User::factory()->create(['facility_id' => $a->id]);

    $this->actingAs($user);
    expect(Lplpo::count())->toBe(1);

    Illuminate\Support\Facades\Auth::forgetGuards();
    $this->actingAs(User::factory()->create(['facility_id' => $b->id]));
    expect(Lplpo::count())->toBe(0);
});

test('permintaan is generated from lplpo only for requested items', function () {
    $facility = Facility::factory()->create();
    $obatA = Obat::factory()->create();
    $obatB = Obat::factory()->create();

    $lplpo = app(GenerateLplpo::class)->handle($facility->id, 11, 2026, [
        ['obat_id' => $obatA->id, 'pemasukan' => 10, 'pemakaian' => 5, 'stok_akhir' => 5, 'jumlah_diminta' => 40],
        ['obat_id' => $obatB->id, 'pemasukan' => 10, 'pemakaian' => 5, 'stok_akhir' => 5, 'jumlah_diminta' => 0],
    ]);

    expect(fn () => app(CreatePermintaanFromLplpo::class)->handle($lplpo))
        ->toThrow(ValidationException::class);

    app(SubmitLplpo::class)->handle($lplpo);
    app(VerifyLplpo::class)->handle($lplpo->refresh(), true);

    $permintaan = app(CreatePermintaanFromLplpo::class)->handle($lplpo->refresh());

    expect($permintaan->items)->toHaveCount(1)
        ->and($permintaan->items->first()->obat_id)->toBe($obatA->id)
        ->and($permintaan->items->first()->jumlah_diminta)->toBe(40);

    expect(fn () => app(CreatePermintaanFromLplpo::class)->handle($lplpo->refresh()))
        ->toThrow(ValidationException::class);
});

test('partial verification sets item and overall status', function () {
    $facility = Facility::factory()->create();
    $obatA = Obat::factory()->create();
    $obatB = Obat::factory()->create();

    $permintaan = PermintaanObat::create([
        'no_permintaan' => 'PMSK-TEST-001',
        'facility_id' => $facility->id,
        'sumber' => 'mandiri',
        'tanggal' => now()->toDateString(),
        'status' => StatusPermintaan::Dikirim,
    ]);

    $itemA = $permintaan->items()->create(['obat_id' => $obatA->id, 'jumlah_diminta' => 100]);
    $itemB = $permintaan->items()->create(['obat_id' => $obatB->id, 'jumlah_diminta' => 50]);

    $verified = app(VerifyPermintaan::class)->handle($permintaan, [
        $itemA->id => ['jumlah_disetujui' => 100],
        $itemB->id => ['jumlah_disetujui' => 20],
    ]);

    expect($verified->status)->toBe(StatusPermintaan::Sebagian)
        ->and($itemA->refresh()->status_item)->toBe(StatusItemPermintaan::Disetujui)
        ->and($itemB->refresh()->status_item)->toBe(StatusItemPermintaan::Sebagian);
});
