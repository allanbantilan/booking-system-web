<?php

namespace App\Policies;

use App\Models\BackendUser;
use App\Models\MerchantRequest;

class MerchantRequestPolicy
{
    public function before(BackendUser $user): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return false;
    }

    public function viewAny(BackendUser $user): bool
    {
        return false;
    }

    public function view(BackendUser $user, MerchantRequest $merchantRequest): bool
    {
        return false;
    }

    public function create(BackendUser $user): bool
    {
        return false;
    }

    public function update(BackendUser $user, MerchantRequest $merchantRequest): bool
    {
        return false;
    }

    public function delete(BackendUser $user, MerchantRequest $merchantRequest): bool
    {
        return false;
    }
}

