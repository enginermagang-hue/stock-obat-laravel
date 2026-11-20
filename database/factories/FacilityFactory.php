<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Facility> */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'kode' => 'PKM-'.fake()->unique()->numerify('###'),
            'nama' => 'Puskesmas '.fake()->city(),
            'jenis' => 'puskesmas',
            'alamat' => fake()->address(),
            'kontak' => fake()->phoneNumber(),
            'aktif' => true,
        ];
    }
}
