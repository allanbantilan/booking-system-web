<?php

namespace App\Policies;

use App\Models\BackendUser;
use App\Models\Reservation;

class ReservationPolicy
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
        return $user->can('view any reservation');
    }

    public function view(BackendUser $user, Reservation $reservation): bool
    {
        return $user->can('view reservation');
    }

    public function create(BackendUser $user): bool
    {
        return $user->can('create reservation');
    }

    public function update(BackendUser $user, Reservation $reservation): bool
    {
        return $user->can('update reservation');
    }

    public function delete(BackendUser $user, Reservation $reservation): bool
    {
        return $user->can('delete reservation');
    }
}
