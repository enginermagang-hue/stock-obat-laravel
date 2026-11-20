<?php

namespace App\Actions\Rko;

use App\Enums\StatusRko;
use App\Models\Rko;
use Illuminate\Validation\ValidationException;

class ApproveRko
{
    public function handle(Rko $rko, ?int $userId = null): Rko
    {
        if ($rko->status !== StatusRko::Draft) {
            throw ValidationException::withMessages(['status' => 'Hanya RKO draft yang dapat disetujui.']);
        }

        $rko->update([
            'status' => StatusRko::Disetujui,
            'disetujui_oleh' => $userId ?? auth()->id(),
        ]);

        return $rko->refresh();
    }
}
