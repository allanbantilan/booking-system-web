<?php

namespace App\Policies;

use App\Models\BackendUser;

class BackendUserPolicy
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
        return $user->can('view any backend user');
    }

    public function view(BackendUser $user, BackendUser $model): bool
    {
        return $user->can('view backend user');
    }

    public function create(BackendUser $user): bool
    {
        return $user->can('create backend user');
    }

    public function update(BackendUser $user, BackendUser $model): bool
    {
        return $user->can('update backend user');
    }

    public function delete(BackendUser $user, BackendUser $model): bool
    {
        return $user->can('delete backend user');
    }
}
