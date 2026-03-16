<?php

namespace App\Providers;

use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use App\Policies\BackendUserPolicy;
use App\Policies\BookingPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReceiptPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Booking::class => BookingPolicy::class,
        Category::class => CategoryPolicy::class,
        Reservation::class => ReservationPolicy::class,
        Payment::class => PaymentPolicy::class,
        Receipt::class => ReceiptPolicy::class,
        User::class => UserPolicy::class,
        BackendUser::class => BackendUserPolicy::class,
        Role::class => RolePolicy::class,
    ];
}
