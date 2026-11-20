<?php

namespace Database\Factories;

use App\Enums\JenisSumberDana;
use App\Models\SumberDana;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SumberDana> */
class SumberDanaFactory extends Factory
{
    protected $model = SumberDana::class;

    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('SD???'),
            'nama' => 'Sumber Dana '.fake()->word(),
            'jenis' => fake()->randomElement(JenisSumberDana::class),
            'kode_rekening' => fake()->numerify('########'),
            'program' => fake()->sentence(2),
            'aktif' => true,
        ];
    }
}
