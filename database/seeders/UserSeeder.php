<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $pkm = Facility::where('kode', 'PKM-007')->first();

        $users = [
            ['name' => 'Admin RS', 'email' => 'admin@rs.go.id', 'role' => 'admin', 'facility_id' => null],
            ['name' => 'Apoteker RS', 'email' => 'apoteker@rs.go.id', 'role' => 'apoteker', 'facility_id' => null],
            ['name' => 'Petugas Gudang', 'email' => 'gudang@rs.go.id', 'role' => 'gudang', 'facility_id' => null],
            ['name' => 'Petugas Distribusi', 'email' => 'distribusi@rs.go.id', 'role' => 'distribusi', 'facility_id' => null],
            ['name' => 'Admin Puskesmas', 'email' => 'admin@pkm007.go.id', 'role' => 'puskesmas', 'facility_id' => $pkm?->id],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'facility_id' => $data['facility_id'],
                ],
            );
            $user->syncRoles([$data['role']]);
        }
    }
}
