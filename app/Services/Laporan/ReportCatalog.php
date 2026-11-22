<?php

namespace App\Services\Laporan;

use App\Models\User;

class ReportCatalog
{
    /** @return array<string, array{label:string,description:string,group:string,facility_only:bool}> */
    public static function all(): array
    {
        return [
            'stock-card' => [
                'label' => 'Kartu Stok',
                'description' => 'Mutasi ledger per obat/batch.',
                'group' => 'Stok',
                'facility_only' => false,
            ],
            'stock-summary' => [
                'label' => 'Stok Akhir per Periode',
                'description' => 'Rekap jumlah dan nilai stok.',
                'group' => 'Stok',
                'facility_only' => false,
            ],
            'expiry' => [
                'label' => 'Obat Kedaluwarsa',
                'description' => 'Expired dan mendekati expired.',
                'group' => 'Stok',
                'facility_only' => false,
            ],
            'low-stock' => [
                'label' => 'Obat Stok Minimum',
                'description' => 'Stok di bawah ambang batas.',
                'group' => 'Stok',
                'facility_only' => false,
            ],
            'lplpo-recap' => [
                'label' => 'Rekap LPLPO',
                'description' => 'Per puskesmas dan periode + selisih.',
                'group' => 'LPLPO',
                'facility_only' => true,
            ],
            'permintaan-realisasi' => [
                'label' => 'Permintaan vs Realisasi',
                'description' => 'Fulfillment rate per item.',
                'group' => 'Distribusi',
                'facility_only' => true,
            ],
            'rko-realisasi' => [
                'label' => 'RKO vs Realisasi Pembelian',
                'description' => 'Deviasi rencana dan aktual.',
                'group' => 'RKO',
                'facility_only' => false,
            ],
            'anggaran-realisasi' => [
                'label' => 'Anggaran–Realisasi',
                'description' => 'Pagu, komitmen, realisasi, sisa, %.',
                'group' => 'Anggaran',
                'facility_only' => false,
            ],
            'realisasi-obat' => [
                'label' => 'Realisasi per Obat/Kategori',
                'description' => 'Dana apa membeli obat apa.',
                'group' => 'Anggaran',
                'facility_only' => false,
            ],
            'distribusi-nilai' => [
                'label' => 'Nilai Distribusi per Dana',
                'description' => 'Trace sumber dana batch.',
                'group' => 'Distribusi',
                'facility_only' => true,
            ],
            'tren-pemakaian' => [
                'label' => 'Tren Pemakaian Obat',
                'description' => 'Input RKO berikutnya.',
                'group' => 'Tren',
                'facility_only' => true,
            ],
            'opname' => [
                'label' => 'Hasil Stok Opname',
                'description' => 'Sistem vs fisik, selisih, nilai.',
                'group' => 'Opname',
                'facility_only' => true,
            ],
            'opname-selisih' => [
                'label' => 'Riwayat Selisih Opname',
                'description' => 'Tren selisih per obat.',
                'group' => 'Opname',
                'facility_only' => true,
            ],
            'audit-log' => [
                'label' => 'Audit Trail',
                'description' => 'Riwayat perubahan (read-only).',
                'group' => 'Audit',
                'facility_only' => false,
            ],
            'prediksi-realisasi' => [
                'label' => 'Prediksi vs Realisasi',
                'description' => 'Akurasi per obat/periode.',
                'group' => 'Prediksi',
                'facility_only' => true,
            ],
            'prediksi-akurasi' => [
                'label' => 'Evaluasi Akurasi Model',
                'description' => 'MAPE, metode terbaik, versi.',
                'group' => 'Prediksi',
                'facility_only' => true,
            ],
        ];
    }

    /** @return array<string, array{label:string,description:string,group:string,facility_only:bool}> */
    public static function forUser(User $user): array
    {
        return array_filter(
            self::all(),
            fn (array $report, string $key) => self::canView($user, $key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public static function canView(User $user, string $key): bool
    {
        $report = self::all()[$key] ?? null;

        if ($report === null) {
            return false;
        }

        if ($key === 'audit-log') {
            return $user->can('view-audit-log');
        }

        if (! $user->can('view-reports')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'apoteker', 'gudang', 'distribusi'])) {
            return true;
        }

        return $report['facility_only'];
    }

    /** @return array<string, array{label:string,description:string,group:string,facility_only:bool}> */
    public static function groups(): array
    {
        return collect(self::all())
            ->groupBy('group')
            ->map(fn ($reports) => $reports->all())
            ->all();
    }
}
