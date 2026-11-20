<?php

namespace App\Actions\Permintaan;

use App\Enums\StatusPermintaan;
use App\Models\PermintaanObat;
use Illuminate\Validation\ValidationException;

class SubmitPermintaan
{
    public function handle(PermintaanObat $permintaan): PermintaanObat
    {
        if ($permintaan->status !== StatusPermintaan::Draft) {
            throw ValidationException::withMessages(['status' => 'Hanya permintaan draft yang dapat dikirim.']);
        }

        if ($permintaan->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Permintaan belum memiliki item.']);
        }

        $permintaan->update(['status' => StatusPermintaan::Dikirim]);

        return $permintaan->refresh();
    }
}
