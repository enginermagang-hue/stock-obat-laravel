<?php

namespace App\Actions\Lplpo;

use App\Enums\StatusLplpo;
use App\Models\Lplpo;
use Illuminate\Validation\ValidationException;

class VerifyLplpo
{
    public function handle(Lplpo $lplpo, bool $approve, ?int $userId = null, ?string $catatan = null): Lplpo
    {
        if ($lplpo->status !== StatusLplpo::Dikirim) {
            throw ValidationException::withMessages(['status' => 'Hanya LPLPO berstatus dikirim yang dapat diverifikasi.']);
        }

        $lplpo->update([
            'status' => $approve ? StatusLplpo::Diverifikasi : StatusLplpo::Ditolak,
            'verified_by' => $userId ?? auth()->id(),
            'verified_at' => now(),
            'catatan' => $catatan ?? $lplpo->catatan,
        ]);

        return $lplpo->refresh()->load('items');
    }
}
