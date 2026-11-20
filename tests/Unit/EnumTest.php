<?php

use App\Enums\BentukSediaan;
use App\Enums\GolonganObat;
use App\Enums\JenisSumberDana;

test('enum values match the blueprint', function () {
    expect(BentukSediaan::Tablet->value)->toBe('tablet')
        ->and(BentukSediaan::Injeksi->value)->toBe('injeksi')
        ->and(GolonganObat::BebasTerbatas->value)->toBe('bebas_terbatas')
        ->and(GolonganObat::Keras->value)->toBe('keras')
        ->and(JenisSumberDana::ApbdKab->value)->toBe('apbd_kab')
        ->and(JenisSumberDana::Bok->value)->toBe('bok');
});
