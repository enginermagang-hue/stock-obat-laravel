<?php

namespace Database\Factories;

use App\Enums\BentukSediaan;
use App\Enums\GolonganObat;
use App\Models\KategoriObat;
use App\Models\Obat;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Obat> */
class ObatFactory extends Factory
{
    protected $model = Obat::class;

    public function definition(): array
    {
        return [
            'kode_obat' => 'OBT-'.fake()->unique()->numerify('####'),
            'nama' => fake()->unique()->word(),
            'nama_generik' => fake()->word(),
            'kategori_id' => KategoriObat::factory(),
            'bentuk_sediaan' => fake()->randomElement(BentukSediaan::class),
            'kekuatan' => '500 mg',
            'satuan' => 'tablet',
            'golongan' => fake()->randomElement(GolonganObat::class),
            'no_izin_edar' => fake()->numerify('DE-###-####'),
            'produsen' => fake()->company(),
            'harga_satuan_terakhir' => fake()->randomFloat(2, 100, 5000),
            'stok_minimum' => fake()->numberBetween(10, 100),
            'butuh_cold_chain' => false,
            'aktif' => true,
        ];
    }
}
