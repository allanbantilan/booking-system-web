<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
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
                'events' => Event::query()->count(),
                'bookingHistory' => Booking::query()->where('user_id', $user->id)->count(),
                'confirmedBookings' => Booking::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'confirmed')
                    ->count(),
            ],
        ]);
    }
}
