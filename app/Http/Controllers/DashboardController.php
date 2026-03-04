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

        $events = Event::query()
            ->with('creator:id,name')
            ->orderBy('event_date')
            ->get();

        $bookings = Booking::query()
            ->with('event:id,title,event_date,location')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'events' => $events,
            'bookings' => $bookings,
            'totals' => [
                'events' => $events->count(),
                'bookingHistory' => $bookings->count(),
                'confirmedBookings' => $bookings->where('status', 'confirmed')->count(),
            ],
        ]);
    }
}