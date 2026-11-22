<?php

namespace App\Actions\Laporan;

use App\Actions\Anggaran\BuildLaporanAnggaranRealisasi;

class AnggaranRealisasiReport
{
    public function __construct(private readonly BuildLaporanAnggaranRealisasi $builder) {}

    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'sumber_dana' => 'Sumber dana',
            'unit' => 'Unit',
            'pagu' => 'Pagu',
            'komitmen' => 'Komitmen bersih',
            'realisasi' => 'Realisasi',
            'sisa' => 'Sisa pagu',
            'persen_realisasi' => 'Realisasi (%)',
        ];

        $tahun = (int) ($params['tahun'] ?? now()->year);
        $facilityId = $params['facility_id'] ?? null;

        $data = $this->builder->handle($tahun, null, $facilityId);

        $rows = $data['rows']->map(fn (array $row) => [
            'sumber_dana' => $row['anggaran']->sumberDana?->nama ?? '—',
            'unit' => $row['anggaran']->unitLabel(),
            'pagu' => $row['pagu'],
            'komitmen' => $row['komitmen_bersih'],
            'realisasi' => $row['realisasi'],
            'sisa' => $row['sisa'],
            'persen_realisasi' => $row['persen_realisasi'],
        ])->all();

        if ($rows !== []) {
            $rows[] = [
                'sumber_dana' => 'Total',
                'unit' => '',
                'pagu' => $data['pagu'],
                'komitmen' => $data['komitmen'],
                'realisasi' => $data['realisasi'],
                'sisa' => $data['sisa'],
                'persen_realisasi' => $data['pagu'] > 0
                    ? round($data['realisasi'] / $data['pagu'] * 100, 1)
                    : 0,
            ];
        }

        return ['headings' => $headings, 'rows' => $rows];
    }
}
