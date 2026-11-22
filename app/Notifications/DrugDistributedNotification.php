<?php

namespace App\Notifications;

use App\Models\Distribution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DrugDistributedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Distribution $distribution) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kode' => 'DRUG_DISTRIBUTED',
            'judul' => 'Distribusi obat dikirim',
            'pesan' => "Surat jalan {$this->distribution->no_surat} telah dikirim ke fasilitas tujuan.",
            'distribution_id' => $this->distribution->id,
        ];
    }
}
