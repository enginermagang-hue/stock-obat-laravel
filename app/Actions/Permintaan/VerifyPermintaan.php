<?php

namespace App\Actions\Permintaan;

use App\Enums\StatusItemPermintaan;
use App\Enums\StatusPermintaan;
use App\Models\PermintaanObat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyPermintaan
{
    /**
     * @param  array<int, array{jumlah_disetujui:int,status_item?:string,keterangan?:?string}>  $decisions  keyed by item id
     */
    public function handle(PermintaanObat $permintaan, array $decisions, ?int $userId = null, ?string $catatan = null): PermintaanObat
    {
        if (! in_array($permintaan->status, [StatusPermintaan::Dikirim, StatusPermintaan::Diterima], true)) {
            throw ValidationException::withMessages(['status' => 'Permintaan tidak dapat diverifikasi pada status ini.']);
        }

        return DB::transaction(function () use ($permintaan, $decisions, $userId, $catatan) {
            $permintaan->load('items');
            $statuses = [];

            foreach ($permintaan->items as $item) {
                $decision = $decisions[$item->id] ?? null;

                if ($decision === null) {
                    $jumlah = $item->jumlah_diminta;
                    $status = StatusItemPermintaan::Disetujui;
                } else {
                    $jumlah = max(0, min((int) $decision['jumlah_disetujui'], $item->jumlah_diminta));
                    $status = ! empty($decision['status_item'])
                        ? StatusItemPermintaan::from($decision['status_item'])
                        : ($jumlah === 0
                            ? StatusItemPermintaan::Ditolak
                            : ($jumlah === $item->jumlah_diminta ? StatusItemPermintaan::Disetujui : StatusItemPermintaan::Sebagian));
                }

                $item->update([
                    'jumlah_disetujui' => $jumlah,
                    'status_item' => $status,
                    'keterangan' => $decision['keterangan'] ?? $item->keterangan,
                ]);
                $statuses[] = $status;
            }

            $allRejected = collect($statuses)->every(fn ($s) => $s === StatusItemPermintaan::Ditolak);
            $allApproved = collect($statuses)->every(fn ($s) => in_array($s, [StatusItemPermintaan::Disetujui, StatusItemPermintaan::Selesai], true));

            $overall = $allRejected
                ? StatusPermintaan::Ditolak
                : ($allApproved ? StatusPermintaan::Disetujui : StatusPermintaan::Sebagian);

            $permintaan->update([
                'status' => $overall,
                'verified_by' => $userId ?? auth()->id(),
                'verified_at' => now(),
                'catatan_verifikasi' => $catatan,
            ]);

            return $permintaan->refresh()->load('items.obat');
        });
    }
}
