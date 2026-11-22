<?php

namespace App\Actions\Distribusi;

use App\Enums\StatusDistribusi;
use App\Models\BatchObat;
use App\Models\DistributionItem;
use App\Models\Obat;
use App\Scopes\FacilityScope;
use Illuminate\Validation\ValidationException;

class SelectBatchFefo
{
    /**
     * Alokasikan jumlah obat ke batch dengan tanggal kedaluwarsa terdekat.
     *
     * Batch yang sudah dialokasikan pada distribusi draft/disetujui dianggap
     * terpakai agar satu batch tidak dialokasikan dua kali sebelum dikirim.
     *
     * @return array<int, array{batch: BatchObat, jumlah: int}>
     */
    public function handle(int $obatId, int $jumlah, ?int $excludeDistributionId = null, bool $lock = true): array
    {
        if ($jumlah < 1) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah distribusi harus lebih dari nol.']);
        }

        $batches = BatchObat::query()
            ->where('obat_id', $obatId)
            ->fefo()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get();

        $allocations = [];
        $remaining = $jumlah;

        foreach ($batches as $batch) {
            $available = $this->availableInBatch($batch, $excludeDistributionId);

            if ($available === 0) {
                continue;
            }

            $take = min($remaining, $available);
            $allocations[] = ['batch' => $batch, 'jumlah' => $take];
            $remaining -= $take;

            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            $nama = Obat::query()->whereKey($obatId)->value('nama') ?? 'Obat';

            throw ValidationException::withMessages([
                'items' => "Stok {$nama} tidak mencukupi. Kurang {$remaining} unit.",
            ]);
        }

        return $allocations;
    }

    /**
     * Total unit yang masih dapat dialokasikan tanpa mengunci batch.
     */
    public function available(int $obatId): int
    {
        return (int) BatchObat::query()
            ->where('obat_id', $obatId)
            ->fefo()
            ->get()
            ->sum(fn (BatchObat $batch) => $this->availableInBatch($batch));
    }

    private function availableInBatch(BatchObat $batch, ?int $excludeDistributionId = null): int
    {
        $reserved = DistributionItem::query()
            ->where('batch_id', $batch->id)
            ->whereHas('distribution', function ($query) use ($excludeDistributionId) {
                $query->withoutGlobalScope(FacilityScope::class)
                    ->whereIn('status', [StatusDistribusi::Draft, StatusDistribusi::Disetujui])
                    ->when($excludeDistributionId, fn ($q) => $q->where('id', '!=', $excludeDistributionId));
            })
            ->sum('jumlah');

        return max(0, (int) $batch->jumlah_sisa - (int) $reserved);
    }
}
