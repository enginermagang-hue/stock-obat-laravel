<?php

namespace App\Policies;

use App\Models\User;

class SumberDanaPolicy extends MasterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'apoteker']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'apoteker']);
    }
}
