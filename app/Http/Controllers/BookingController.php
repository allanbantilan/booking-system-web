<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function store(Request $request, int $eventId): RedirectResponse
    {
        return back()->with('error', "Booking for event #{$eventId} is not implemented yet. Add your manual booking logic here.");
    }

    public function cancel(int $bookingId): RedirectResponse
    {
        return back()->with('error', "Booking #{$bookingId} cancel is not implemented yet. Add your manual cancel logic here.");
    }

    public function history(Request $request): Response
    {
        $bookings = Booking::query()
            ->with('event:id,title,event_date,location')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return Inertia::render('BookingHistory', [
            'bookings' => $bookings,
        ]);
    }
}
