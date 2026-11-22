<?php

namespace App\Actions\Distribusi;

use App\Enums\StatusDistribusi;
use App\Enums\StatusItemPermintaan;
use App\Enums\StatusPermintaan;
use App\Models\Distribution;
use App\Models\DistributionItem;
use App\Models\PermintaanObat;
use App\Models\User;
use App\Scopes\FacilityScope;
use App\Services\Distribusi\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmDistribution
{
    public function __construct(private readonly DocumentNumber $documentNumber) {}

    /**
     * Konfirmasi penerimaan oleh puskesmas dan perbarui status pemenuhan permintaan.
     */
    public function handle(
        Distribution $distribution,
        ?string $penerimaNama = null,
        ?int $userId = null,
        ?string $fileTandaTerima = null,
        ?string $penerimaNip = null,
        ?string $penerimaJabatan = null,
        ?string $penerimaUnit = null,
    ): Distribution {
        return DB::transaction(function () use ($distribution, $penerimaNama, $userId, $fileTandaTerima, $penerimaNip, $penerimaJabatan, $penerimaUnit) {
            $locked = Distribution::withoutGlobalScope(FacilityScope::class)
                ->whereKey($distribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $user = $userId !== null ? User::find($userId) : auth()->user();

            if ($user?->facility_id !== null && (int) $user->facility_id !== (int) $locked->facility_id) {
                throw ValidationException::withMessages([
                    'facility_id' => 'Surat jalan ini bukan untuk fasilitas Anda.',
                ]);
            }

            if ($locked->status === StatusDistribusi::Diterima) {
                return $locked->load('items.batch', 'facility', 'permintaan');
            }

            if ($locked->status !== StatusDistribusi::Dikirim) {
                throw ValidationException::withMessages([
                    'status' => 'Surat jalan belum dikirim sehingga belum dapat dikonfirmasi.',
                ]);
            }

            $locked->loadMissing('facility');

            $locked->update([
                'status' => StatusDistribusi::Diterima,
                'penerima_nama' => $penerimaNama ?: $locked->penerima_nama,
                'penerima_nip' => $penerimaNip ?: ($locked->penerima_nip ?: $user?->nip),
                'penerima_jabatan' => $penerimaJabatan ?: ($locked->penerima_jabatan ?: $user?->jabatan),
                'penerima_unit' => $penerimaUnit ?: ($locked->penerima_unit ?: $locked->facility?->nama),
                'no_bast' => $locked->no_bast ?: $this->documentNumber->bast(),
                'tanggal_diterima' => now()->toDateString(),
                'file_tanda_terima' => $fileTandaTerima ?: $locked->file_tanda_terima,
            ]);

            $this->refreshPermintaan($locked);

            return $locked->refresh()->load('items.batch', 'facility', 'permintaan');
        });
    }

    private function refreshPermintaan(Distribution $distribution): void
    {
        if ($distribution->permintaan_id === null) {
            return;
        }

        $permintaan = PermintaanObat::withoutGlobalScope(FacilityScope::class)
            ->with('items')
            ->find($distribution->permintaan_id);

        if ($permintaan === null) {
            return;
        }

        $allDone = true;

        foreach ($permintaan->items as $item) {
            $approved = (int) ($item->jumlah_disetujui ?? $item->jumlah_diminta);

            if ($approved < 1) {
                continue;
            }

            $received = (int) DistributionItem::query()
                ->where('permintaan_item_id', $item->id)
                ->whereHas('distribution', fn ($query) => $query
                    ->withoutGlobalScope(FacilityScope::class)
                    ->where('status', StatusDistribusi::Diterima->value))
                ->sum('jumlah');

            if ($received >= $approved) {
                $item->update(['status_item' => StatusItemPermintaan::Selesai]);
            } else {
                $allDone = false;
            }
        }

        $permintaan->update([
            'status' => $allDone ? StatusPermintaan::Selesai : StatusPermintaan::Diproses,
        ]);
    }
}
