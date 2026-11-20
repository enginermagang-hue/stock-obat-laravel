<?php

namespace App\Console\Commands;

use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\User;
use App\Notifications\StockAlertNotification;
use Illuminate\Console\Command;

class CheckStockAlerts extends Command
{
    protected $signature = 'stock:check-alerts';

    protected $description = 'Kirim notifikasi database untuk stok menipis dan obat mendekati expired';

    public function handle(): int
    {
        $expiring = BatchObat::expiring(90)->count();

        $lowStock = Obat::query()
            ->whereRaw('(select coalesce(sum(jumlah_sisa), 0) from batch_obat where batch_obat.obat_id = obat.id) <= obat.stok_minimum')
            ->count();

        $users = User::role(['apoteker', 'admin'])->get();

        foreach ($users as $user) {
            if ($expiring > 0) {
                $this->notifyOnce($user, 'OBAT_EXPIRING', 'Obat mendekati kedaluwarsa', "{$expiring} batch akan kedaluwarsa dalam 90 hari.");
            }

            if ($lowStock > 0) {
                $this->notifyOnce($user, 'STOK_MENIPIS', 'Stok obat menipis', "{$lowStock} obat berada pada atau di bawah stok minimum.");
            }
        }

        $this->info("Cek selesai: {$expiring} batch expiring, {$lowStock} obat menipis.");

        return self::SUCCESS;
    }

    private function notifyOnce(User $user, string $kode, string $judul, string $pesan): void
    {
        $exists = $user->notifications()
            ->whereDate('created_at', today())
            ->get()
            ->contains(fn ($notification) => ($notification->data['kode'] ?? null) === $kode);

        if (! $exists) {
            $user->notify(new StockAlertNotification($kode, $judul, $pesan));
        }
    }
}
