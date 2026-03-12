<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function store(Request $request, int $bookingId): RedirectResponse
    {
        return back()->with('error', "Reservation for booking #{$bookingId} is not implemented yet. Add your manual booking logic here.");
    }

    public function cancel(int $reservationId): RedirectResponse
    {
        return back()->with('error', "Reservation #{$reservationId} cancel is not implemented yet. Add your manual cancel logic here.");
    }

    public function history(Request $request): Response
    {
        $reservations = Reservation::query()
            ->with(['booking:id,title,event_date,location,category_id', 'booking.category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return Inertia::render('BookingHistory', [
            'reservations' => $reservations,
        ]);
    }
}
