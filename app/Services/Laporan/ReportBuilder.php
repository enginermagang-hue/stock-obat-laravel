<?php

namespace App\Services\Laporan;

use App\Actions\Laporan\AnggaranRealisasiReport;
use App\Actions\Laporan\AuditLogReport;
use App\Actions\Laporan\DistribusiNilaiReport;
use App\Actions\Laporan\ExpiryReport;
use App\Actions\Laporan\KartuStokReport;
use App\Actions\Laporan\LowStockReport;
use App\Actions\Laporan\LplpoRecapReport;
use App\Actions\Laporan\OpnameReport;
use App\Actions\Laporan\OpnameSelisihReport;
use App\Actions\Laporan\PermintaanRealisasiReport;
use App\Actions\Laporan\PrediksiAkurasiReport;
use App\Actions\Laporan\PrediksiRealisasiReport;
use App\Actions\Laporan\RealisasiObatReport;
use App\Actions\Laporan\RkoRealisasiReport;
use App\Actions\Laporan\StokAkhirReport;
use App\Actions\Laporan\TrenPemakaianReport;
use InvalidArgumentException;

class ReportBuilder
{
    private const REPORTS = [
        'stock-card' => KartuStokReport::class,
        'stock-summary' => StokAkhirReport::class,
        'expiry' => ExpiryReport::class,
        'low-stock' => LowStockReport::class,
        'lplpo-recap' => LplpoRecapReport::class,
        'permintaan-realisasi' => PermintaanRealisasiReport::class,
        'rko-realisasi' => RkoRealisasiReport::class,
        'anggaran-realisasi' => AnggaranRealisasiReport::class,
        'realisasi-obat' => RealisasiObatReport::class,
        'distribusi-nilai' => DistribusiNilaiReport::class,
        'tren-pemakaian' => TrenPemakaianReport::class,
        'opname' => OpnameReport::class,
        'opname-selisih' => OpnameSelisihReport::class,
        'audit-log' => AuditLogReport::class,
        'prediksi-realisasi' => PrediksiRealisasiReport::class,
        'prediksi-akurasi' => PrediksiAkurasiReport::class,
    ];

    /**
     * @return array{key:string,title:string,headings:array<string,string>,rows:array<int,array<string,mixed>>}
     */
    public function build(string $key, array $params): array
    {
        $catalog = ReportCatalog::all();

        if (! isset($catalog[$key], self::REPORTS[$key])) {
            throw new InvalidArgumentException("Laporan tidak dikenal: {$key}");
        }

        $result = app(self::REPORTS[$key])->handle($params);

        return [
            'key' => $key,
            'title' => $catalog[$key]['label'],
            'headings' => $result['headings'],
            'rows' => $result['rows'],
        ];
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys(self::REPORTS);
    }
}
