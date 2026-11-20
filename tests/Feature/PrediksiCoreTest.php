<?php

use App\Actions\Lplpo\GenerateLplpo;
use App\Actions\Lplpo\SubmitLplpo;
use App\Actions\Lplpo\VerifyLplpo;
use App\Actions\Prediksi\EvaluasiAkurasi;
use App\Actions\Prediksi\PrediksiKebutuhanObat;
use App\Actions\Rko\BuildRko;
use App\Enums\MetodePrediksi;
use App\Enums\StatusPrediksi;
use App\Models\Facility;
use App\Models\Obat;
use App\Models\PrediksiObat;
use App\Models\SumberDana;
use App\Models\Supplier;
use App\Services\Prediksi\HoltWinters;

test('holt winters is deterministic and produces intervals', function () {
    $series = [20, 24, 22, 28, 26, 32, 30, 36, 34, 40, 38, 44];

    $first = app(HoltWinters::class)->forecast($series, 1);
    $second = app(HoltWinters::class)->forecast($series, 1);

    expect($first)->toBe($second)
        ->and($first['forecast'])->toBeGreaterThan(0)
        ->and($first['upper'])->toBeGreaterThanOrEqual($first['forecast'])
        ->and($first['lower'])->toBeLessThanOrEqual($first['forecast']);
});

test('prediction uses holt winters when enough history exists', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create();

    for ($month = 1; $month <= 12; $month++) {
        $lplpo = app(GenerateLplpo::class)->handle($facility->id, $month, 2025, [[
            'obat_id' => $obat->id,
            'pemasukan' => 100,
            'pemakaian' => 10 + $month,
            'stok_akhir' => 50,
            'jumlah_diminta' => 0,
        ]]);
        app(SubmitLplpo::class)->handle($lplpo);
        app(VerifyLplpo::class)->handle($lplpo->refresh(), true);
    }

    $results = app(PrediksiKebutuhanObat::class)->handle(1, 2026, $facility->id);

    $prediction = $results->firstWhere('obat_id', $obat->id);

    expect($prediction)->not->toBeNull()
        ->and($prediction->metode_model)->toBe(MetodePrediksi::HoltWinters)
        ->and((float) $prediction->nilai_prediksi)->toBeGreaterThan(0)
        ->and($prediction->mape)->not->toBeNull();
});

test('prediction falls back to historical average for short history', function () {
    $facility = Facility::factory()->create();
    $obat = Obat::factory()->create(['stok_minimum' => 40]);

    $lplpo = app(GenerateLplpo::class)->handle($facility->id, 1, 2026, [[
        'obat_id' => $obat->id,
        'pemasukan' => 10,
        'pemakaian' => 5,
        'stok_akhir' => 5,
        'jumlah_diminta' => 0,
    ]]);
    app(SubmitLplpo::class)->handle($lplpo);
    app(VerifyLplpo::class)->handle($lplpo->refresh(), true);

    $results = app(PrediksiKebutuhanObat::class)->handle(2, 2026, $facility->id);
    $prediction = $results->firstWhere('obat_id', $obat->id);

    expect($prediction->metode_model)->toBe(MetodePrediksi::Historis)
        ->and((float) $prediction->nilai_prediksi)->toBe(5.0);
});

test('validated prediction is used by ai rko', function () {
    $obat = Obat::factory()->create(['harga_satuan_terakhir' => 1000, 'stok_minimum' => 0]);
    $supplier = Supplier::factory()->create();
    $sumberDana = SumberDana::factory()->create();

    $prediction = PrediksiObat::create([
        'obat_id' => $obat->id,
        'facility_id' => null,
        'periode_bulan' => 12,
        'periode_tahun' => 2026,
        'nilai_prediksi' => 500,
        'interval_bawah' => 450,
        'interval_atas' => 550,
        'confidence_score' => 0.85,
        'metode_model' => MetodePrediksi::HoltWinters,
        'versi_model' => 'holt-winters-1.0',
        'tanggal_prediksi' => now()->toDateString(),
        'status' => StatusPrediksi::Divalidasi,
        'mape' => 12.5,
    ]);

    $rko = app(BuildRko::class)->handle(2026, 12, [
        'metode' => 'ai',
        'sumber_dana_id' => $sumberDana->id,
        'supplier_id' => $supplier->id,
    ]);

    $item = $rko->items->firstWhere('obat_id', $obat->id);

    expect($rko->metode->value)->toBe('ai')
        ->and($item->sumber)->toBe('ai')
        ->and((float) $item->estimasi_pemakaian)->toBe(500.0)
        ->and($item->prediksi_id)->toBe($prediction->id);
});

test('evaluation marks prediction as validated', function () {
    $obat = Obat::factory()->create();

    $prediction = PrediksiObat::create([
        'obat_id' => $obat->id,
        'periode_bulan' => 1,
        'periode_tahun' => 2027,
        'nilai_prediksi' => 100,
        'metode_model' => MetodePrediksi::HoltWinters,
        'versi_model' => 'holt-winters-1.0',
        'tanggal_prediksi' => now()->toDateString(),
        'status' => StatusPrediksi::Draft,
        'mape' => 15,
    ]);

    app(EvaluasiAkurasi::class)->handle($prediction);

    expect($prediction->refresh()->status)->toBe(StatusPrediksi::Divalidasi)
        ->and($prediction->evaluated_at)->not->toBeNull();
});
