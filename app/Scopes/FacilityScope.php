<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class FacilityScope implements Scope
{
    /** Role yang melihat data lintas fasilitas. */
    public const UNRESTRICTED_ROLES = ['admin', 'apoteker', 'gudang', 'distribusi'];

    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->hasRole(self::UNRESTRICTED_ROLES)) {
            return;
        }

        if ($user->facility_id !== null) {
            $builder->where($model->qualifyColumn('facility_id'), $user->facility_id);
        }
    }
}
