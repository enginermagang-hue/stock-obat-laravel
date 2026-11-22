<?php

namespace App\Services\Distribusi;

use App\Models\Distribution;
use App\Scopes\FacilityScope;

class DocumentNumber
{
    public function sbbk(): string
    {
        return $this->next('SBBK', 'no_sbbk');
    }

    public function bast(): string
    {
        return $this->next('BAST', 'no_bast');
    }

    private function next(string $prefix, string $column): string
    {
        $numberPrefix = $prefix.'-'.now()->format('Y').'-';

        $last = Distribution::withoutGlobalScope(FacilityScope::class)
            ->where($column, 'like', $numberPrefix.'%')
            ->orderByDesc('id')
            ->value($column);

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $numberPrefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
