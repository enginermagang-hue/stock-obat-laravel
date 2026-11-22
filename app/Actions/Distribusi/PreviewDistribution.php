<?php

namespace App\Actions\Distribusi;

use App\Enums\StatusDistribusi;
use App\Enums\StatusItemPermintaan;
use App\Models\DistributionItem;
use App\Models\PermintaanObat;
use App\Scopes\FacilityScope;

class PreviewDistribution
{
    public function __construct(private readonly SelectBatchFefo $selectBatchFefo) {}

    /**
     * Hitung item dan alokasi FEFO tanpa menyimpan distribusi.
     *
     * @return array{
     *     no_permintaan: string,
     *     facility: ?string,
     *     status: string,
     *     items: array<int, array<string, mixed>>,
     *     total: float,
     *     warnings: array<int, string>
     * }
     */
    public function handle(PermintaanObat $permintaan): array
    {
        $permintaan->loadMissing(['facility', 'items.obat']);

        $items = [];
        $warnings = [];
        $total = 0.0;

        foreach ($permintaan->items as $item) {
            if (in_array($item->status_item, [StatusItemPermintaan::Ditolak, StatusItemPermintaan::Backorder], true)) {
                continue;
            }

            $approved = (int) ($item->jumlah_disetujui ?? $item->jumlah_diminta);

            if ($approved < 1) {
                continue;
            }

            $fulfilled = (int) DistributionItem::query()
                ->where('permintaan_item_id', $item->id)
                ->whereHas('distribution', fn ($query) => $query
                    ->withoutGlobalScope(FacilityScope::class)
                    ->where('status', '!=', StatusDistribusi::Dibatalkan->value))
                ->sum('jumlah');

            $remaining = $approved - $fulfilled;

            if ($remaining < 1) {
                continue;
            }

            $allocatable = min($remaining, $this->selectBatchFefo->available($item->obat_id));
            $shortage = $remaining - $allocatable;
            $batches = [];
            $subtotal = 0.0;

            if ($allocatable > 0) {
                foreach ($this->selectBatchFefo->handle($item->obat_id, $allocatable, null, false) as $allocation) {
                    $batch = $allocation['batch'];
                    $lineTotal = $allocation['jumlah'] * (float) $batch->harga_beli;

                    $subtotal += $lineTotal;
                    $batches[] = [
                        'no_batch' => $batch->no_batch,
                        'tanggal_kedaluwarsa' => $batch->tanggal_kedaluwarsa?->format('d-m-Y'),
                        'jumlah' => $allocation['jumlah'],
                        'harga_satuan' => (float) $batch->harga_beli,
                        'subtotal' => $lineTotal,
                    ];
                }
            }

            $total += $subtotal;

            if ($shortage > 0) {
                $warnings[] = 'Stok '.$item->obat?->nama.' tidak mencukupi: kurang '.$shortage.' unit.';
            }

            $items[] = [
                'obat' => $item->obat?->nama,
                'kode' => $item->obat?->kode_obat,
                'disetujui' => $approved,
                'terpenuhi' => $fulfilled,
                'sisa' => $remaining,
                'dialokasikan' => $allocatable,
                'kekurangan' => $shortage,
                'batches' => $batches,
                'subtotal' => $subtotal,
            ];
        }

        if ($items === []) {
            $warnings[] = 'Tidak ada item yang masih dapat dipenuhi untuk permintaan ini.';
        }

        return [
            'no_permintaan' => $permintaan->no_permintaan,
            'facility' => $permintaan->facility?->nama,
            'status' => $permintaan->status->value,
            'items' => $items,
            'total' => $total,
            'warnings' => $warnings,
        ];
    }
}
