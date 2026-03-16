<?php

namespace App\Policies;

use App\Models\BackendUser;
use App\Models\Payment;

class PaymentPolicy
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
        return $user->can('view any payment');
    }

    public function view(BackendUser $user, Payment $payment): bool
    {
        return $user->can('view payment');
    }

    public function create(BackendUser $user): bool
    {
        return $user->can('create payment');
    }

    public function update(BackendUser $user, Payment $payment): bool
    {
        return $user->can('update payment');
    }

    public function delete(BackendUser $user, Payment $payment): bool
    {
        return $user->can('delete payment');
    }
}
