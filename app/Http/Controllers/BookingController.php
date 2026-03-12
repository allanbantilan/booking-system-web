<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(): Response
    {
        $bookings = Booking::query()
            ->with('category')
            ->with('media')
            ->orderBy('event_date')
            ->get();

        return Inertia::render('Bookings', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Booking $booking): Response
    {
        $booking->load(['category', 'creator:id,name', 'media']);

        return Inertia::render('BookingShow', [
            'booking' => $booking,
        ]);
    }
}
