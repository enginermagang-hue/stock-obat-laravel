<?php

namespace Database\Seeders;

use App\Enums\JenisSumberDana;
use App\Models\Facility;
use App\Models\KategoriObat;
use App\Models\Obat;
use App\Models\SumberDana;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        Facility::updateOrCreate(
            ['kode' => 'RS-000'],
            ['id' => 0, 'nama' => 'Rumah Sakit Umum Pusat', 'jenis' => 'rs', 'aktif' => true],
        );

        foreach ([
            ['PKM-007', 'Puskesmas Sukamaju'],
            ['PKM-008', 'Puskesmas Margahayu'],
            ['PKM-009', 'Puskesmas Cibeureum'],
        ] as [$kode, $nama]) {
            Facility::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'jenis' => 'puskesmas', 'aktif' => true]);
        }

        foreach (JenisSumberDana::cases() as $jenis) {
            SumberDana::updateOrCreate(
                ['kode' => strtoupper($jenis->value)],
                ['nama' => $jenis->name, 'jenis' => $jenis, 'aktif' => true],
            );
        }

        foreach ([
            ['KAT-01', 'Antibiotik'],
            ['KAT-02', 'Analgesik & antipiretik'],
            ['KAT-03', 'Antihipertensi'],
            ['KAT-04', 'Obat luar'],
        ] as [$kode, $nama]) {
            KategoriObat::updateOrCreate(['kode' => $kode], ['nama' => $nama]);
        }

        foreach ([
            ['SUP-001', 'PT Farma Nusantara'],
            ['SUP-002', 'CV Medika Jaya'],
            ['SUP-003', 'PT Sehat Bersama'],
        ] as [$kode, $nama]) {
            Supplier::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'aktif' => true]);
        }

        $kategori = KategoriObat::where('kode', 'KAT-01')->first();

        foreach ([
            ['OBT-0001', 'Amoxicillin', '500 mg', 'tablet'],
            ['OBT-0002', 'Paracetamol', '500 mg', 'tablet'],
            ['OBT-0003', 'Ceftriaxone', '1 g', 'injeksi'],
            ['OBT-0004', 'Metformin', '500 mg', 'tablet'],
            ['OBT-0005', 'Amlodipine', '5 mg', 'tablet'],
        ] as [$kode, $nama, $kekuatan, $bentuk]) {
            Obat::updateOrCreate(
                ['kode_obat' => $kode],
                [
                    'nama' => $nama,
                    'nama_generik' => $nama,
                    'kategori_id' => $kategori?->id,
                    'bentuk_sediaan' => $bentuk,
                    'kekuatan' => $kekuatan,
                    'satuan' => $bentuk === 'injeksi' ? 'ampul' : 'tablet',
                    'golongan' => $kode === 'OBT-0003' ? 'keras' : 'bebas',
                    'harga_satuan_terakhir' => 1250,
                    'stok_minimum' => 50,
                    'aktif' => true,
                ],
            );
        }
    }
}
