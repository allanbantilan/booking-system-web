<?php

namespace App\Policies;

use App\Models\BackendUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function before(BackendUser $user): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(BackendUser $user): bool
    {
        return false;
    }

    public function view(BackendUser $user, Role $role): bool
    {
        return false;
    }

    public function create(BackendUser $user): bool
    {
        return false;
    }

    public function update(BackendUser $user, Role $role): bool
    {
        return false;
    }

    public function delete(BackendUser $user, Role $role): bool
    {
        return false;
    }
}
