<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'kode' => 'SUP-'.fake()->unique()->numerify('###'),
            'nama' => fake()->company(),
            'alamat' => fake()->address(),
            'kontak' => fake()->phoneNumber(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'no_izin_usaha' => fake()->numerify('####/####'),
            'aktif' => true,
        ];
    }
}
