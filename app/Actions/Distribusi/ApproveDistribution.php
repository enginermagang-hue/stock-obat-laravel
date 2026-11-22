<?php

namespace App\Actions\Distribusi;

use App\Enums\StatusDistribusi;
use App\Models\Distribution;
use App\Scopes\FacilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveDistribution
{
    public function handle(Distribution $distribution, ?int $userId = null): Distribution
    {
        return DB::transaction(function () use ($distribution, $userId) {
            $locked = Distribution::withoutGlobalScope(FacilityScope::class)
                ->whereKey($distribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === StatusDistribusi::Disetujui) {
                return $locked->load('items');
            }

            if ($locked->status !== StatusDistribusi::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya surat jalan berstatus draft yang dapat disetujui.',
                ]);
            }

            $locked->update([
                'status' => StatusDistribusi::Disetujui,
                'approved_by' => $userId ?? auth()->id(),
            ]);

            return $locked->refresh()->load('items');
        });
    }
}
