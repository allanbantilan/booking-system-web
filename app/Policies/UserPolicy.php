<?php

namespace App\Policies;

use App\Models\BackendUser;
use App\Models\User;

class UserPolicy
{
    public function before(BackendUser $user): ?bool
    {
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(BackendUser $user): bool
    {
        return $user->can('view any user');
    }

    public function view(BackendUser $user, User $model): bool
    {
        return $user->can('view user');
    }

    public function create(BackendUser $user): bool
    {
        return $user->can('create user');
    }

    public function update(BackendUser $user, User $model): bool
    {
        return $user->can('update user');
    }

    public function delete(BackendUser $user, User $model): bool
    {
        return $user->can('delete user');
    }
}
