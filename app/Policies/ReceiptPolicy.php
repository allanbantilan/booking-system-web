<?php

namespace App\Policies;

use App\Models\BackendUser;
use App\Models\Receipt;

class ReceiptPolicy
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
        return $user->can('view any receipt');
    }

    public function view(BackendUser $user, Receipt $receipt): bool
    {
        return $user->can('view receipt');
    }

    public function create(BackendUser $user): bool
    {
        return $user->can('create receipt');
    }

    public function update(BackendUser $user, Receipt $receipt): bool
    {
        return $user->can('update receipt');
    }

    public function delete(BackendUser $user, Receipt $receipt): bool
    {
        return $user->can('delete receipt');
    }
}
