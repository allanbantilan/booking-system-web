<?php

namespace App\Policies;

use App\Models\BackendUser;
use App\Models\Category;

class CategoryPolicy
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
        if ($user->hasRole('merchant')) {
            return true;
        }

        return $user->can('view any category');
    }

    public function view(BackendUser $user, Category $category): bool
    {
        if ($user->hasRole('merchant')) {
            return true;
        }

        return $user->can('view category');
    }

    public function create(BackendUser $user): bool
    {
        if ($user->hasRole('merchant')) {
            return true;
        }

        return $user->can('create category');
    }

    public function update(BackendUser $user, Category $category): bool
    {
        if ($user->hasRole('merchant')) {
            return true;
        }

        return $user->can('update category');
    }

    public function delete(BackendUser $user, Category $category): bool
    {
        if ($user->hasRole('merchant')) {
            return false;
        }

        return $user->can('delete category');
    }
}
