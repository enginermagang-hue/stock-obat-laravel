<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LicenseExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $pesan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kode' => 'LICENSE_EXPIRING',
            'judul' => 'Izin & CDOB perlu diperbarui',
            'pesan' => $this->pesan,
        ];
    }
}
