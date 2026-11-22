<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\User;
use App\Notifications\LicenseExpiringNotification;
use Illuminate\Console\Command;

class CheckLicenseExpiry extends Command
{
    protected $signature = 'licenses:check-expiry';

    protected $description = 'Kirim pengingat izin apotek, izin edar, dan CDOB yang mendekati atau sudah kedaluwarsa';

    public function handle(): int
    {
        $expiring = License::expiring(30)->count();
        $expired = License::expired()->count();

        if ($expiring === 0 && $expired === 0) {
            $this->info('Tidak ada izin yang mendekati kedaluwarsa.');

            return self::SUCCESS;
        }

        $message = "{$expiring} izin akan kedaluwarsa dalam 30 hari dan {$expired} izin sudah kedaluwarsa.";

        User::role(['apoteker', 'admin'])->get()->each(function (User $user) use ($message) {
            $this->notifyOnce($user, $message);
        });

        $this->info("Pengingat izin dikirim: {$message}");

        return self::SUCCESS;
    }

    private function notifyOnce(User $user, string $message): void
    {
        $exists = $user->notifications()
            ->whereDate('created_at', today())
            ->get()
            ->contains(fn ($notification) => ($notification->data['kode'] ?? null) === 'LICENSE_EXPIRING');

        if (! $exists) {
            $user->notify(new LicenseExpiringNotification($message));
        }
    }
}
