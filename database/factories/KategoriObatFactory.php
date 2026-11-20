<?php

namespace Database\Factories;

use App\Models\KategoriObat;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KategoriObat> */
class KategoriObatFactory extends Factory
{
    protected $model = KategoriObat::class;

    public function definition(): array
    {
        return [
            'kode' => 'KAT-'.fake()->unique()->numerify('##'),
            'nama' => fake()->unique()->word(),
            'keterangan' => fake()->sentence(),
        ];
    }
}
