<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'totals' => [
                'bookings' => Booking::query()->count(),
                'bookingHistory' => Reservation::query()->where('user_id', $user->id)->count(),
                'confirmedBookings' => Reservation::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'confirmed')
                    ->count(),
            ],
        ]);
    }
}
