<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function history(Request $request): RedirectResponse
    {
        $historyCount = Booking::query()->where('user_id', $request->user()->id)->count();

        return back()->with('success', "Booking history scaffold is ready. Current records: {$historyCount}.");
    }
}