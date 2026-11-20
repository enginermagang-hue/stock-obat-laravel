<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class MasterPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view-master') || $user->can('manage-master');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('manage-master');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can('manage-master');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('manage-master');
    }
}
