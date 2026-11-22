<?php

namespace App\Actions\Distribusi;

use App\Enums\StatusDistribusi;
use App\Enums\TipeMutasi;
use App\Enums\TipeReferensi;
use App\Models\Distribution;
use App\Models\User;
use App\Notifications\DrugDistributedNotification;
use App\Scopes\FacilityScope;
use App\Services\Distribusi\DocumentNumber;
use App\Services\Stock\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class DistributeDrugs
{
    public function __construct(
        private readonly StockLedgerService $ledger,
        private readonly DocumentNumber $documentNumber,
    ) {}

    /**
     * Kirim surat jalan: tulis stok keluar ke ledger dan ubah status menjadi dikirim.
     */
    public function handle(Distribution $distribution, ?int $userId = null): Distribution
    {
        return DB::transaction(function () use ($distribution, $userId) {
            $locked = Distribution::withoutGlobalScope(FacilityScope::class)
                ->whereKey($distribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === StatusDistribusi::Dikirim) {
                return $locked->load('items.batch', 'facility');
            }

            if ($locked->status !== StatusDistribusi::Disetujui) {
                throw ValidationException::withMessages([
                    'status' => 'Surat jalan harus disetujui terlebih dahulu sebelum dikirim.',
                ]);
            }

            $locked->load('items.batch', 'facility');

            foreach ($locked->items as $item) {
                $this->ledger->record(
                    $item->batch,
                    TipeMutasi::Keluar,
                    $item->jumlah,
                    TipeReferensi::Distribusi,
                    $locked->id,
                    $userId,
                    'Distribusi '.$locked->no_surat,
                );
            }

            $locked->update([
                'status' => StatusDistribusi::Dikirim,
                'pengirim_id' => $userId ?? auth()->id(),
                'no_sbbk' => $locked->no_sbbk ?: $this->documentNumber->sbbk(),
                'tanggal_keluar' => $locked->tanggal_keluar ?: now()->toDateString(),
            ]);

            $this->notifyFacility($locked);

            return $locked->refresh()->load('items.batch', 'facility', 'permintaan');
        });
    }

    private function notifyFacility(Distribution $distribution): void
    {
        $recipients = User::query()
            ->where('facility_id', $distribution->facility_id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new DrugDistributedNotification($distribution));
    }
}
