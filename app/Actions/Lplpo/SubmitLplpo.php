<?php

namespace App\Actions\Lplpo;

use App\Enums\StatusLplpo;
use App\Models\Lplpo;
use Illuminate\Validation\ValidationException;

class SubmitLplpo
{
    public function handle(Lplpo $lplpo): Lplpo
    {
        if ($lplpo->status !== StatusLplpo::Draft) {
            throw ValidationException::withMessages(['status' => 'Hanya LPLPO draft yang dapat dikirim.']);
        }

        if ($lplpo->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'LPLPO belum memiliki item.']);
        }

        $lplpo->update([
            'status' => StatusLplpo::Dikirim,
            'tanggal_penyerahan' => now()->toDateString(),
        ]);

        return $lplpo->refresh();
    }
}
