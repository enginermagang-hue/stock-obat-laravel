<?php

namespace App\Actions\Opname;

use App\Enums\StatusOpname;
use App\Models\StockOpname;
use Illuminate\Validation\ValidationException;

class VerifyOpname
{
    public function handle(StockOpname $opname, ?int $verifierId = null): StockOpname
    {
        if ($opname->status !== StatusOpname::Proses) {
            throw ValidationException::withMessages(['status' => 'Hanya opname berstatus proses yang dapat diverifikasi.']);
        }

        $opname->update([
            'status' => StatusOpname::Diverifikasi,
            'verified_by' => $verifierId ?? auth()->id(),
        ]);

        return $opname->refresh();
    }
}
