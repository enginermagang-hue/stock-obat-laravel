<?php

namespace Database\Factories;

use App\Enums\JenisLicense;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<License> */
class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        return [
            'jenis' => fake()->randomElement(JenisLicense::cases()),
            'nomor' => 'IZIN-'.fake()->unique()->numerify('####'),
            'tanggal_terbit' => now()->subYear(),
            'tanggal_kedaluwarsa' => now()->addYears(3),
            'file_path' => null,
            'keterangan' => null,
        ];
    }

    public function expiringSoon(int $days = 10): static
    {
        return $this->state(fn () => [
            'tanggal_kedaluwarsa' => today()->addDays($days),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'tanggal_kedaluwarsa' => today()->subDays(10),
        ]);
    }
}
