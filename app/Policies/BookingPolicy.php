<?php

namespace App\Policies;

use App\Models\BackendUser;
use App\Models\Booking;

class BookingPolicy
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
        return $user->can('view any booking');
    }

    public function view(BackendUser $user, Booking $booking): bool
    {
        if (! $user->can('view booking')) {
            return false;
        }

        return $this->ownsIfMerchant($user, $booking);
    }

    public function create(BackendUser $user): bool
    {
        return $user->can('create booking');
    }

    public function update(BackendUser $user, Booking $booking): bool
    {
        if (! $user->can('update booking')) {
            return false;
        }

        return $this->ownsIfMerchant($user, $booking);
    }

    public function delete(BackendUser $user, Booking $booking): bool
    {
        if (! $user->can('delete booking')) {
            return false;
        }

        return $this->ownsIfMerchant($user, $booking);
    }

    private function ownsIfMerchant(BackendUser $user, Booking $booking): bool
    {
        if (! $user->hasRole('merchant')) {
            return true;
        }

        return (int) $booking->created_by === (int) $user->id;
    }
}
