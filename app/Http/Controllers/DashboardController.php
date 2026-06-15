<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Reservation;
use App\Types\StatusType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $reservations = Reservation::query()
            ->with(['booking:id,title,location,event_date,booking_type,category_id', 'booking.category:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $upcomingReservation = $reservations
            ->filter(fn (Reservation $reservation) => $reservation->status !== StatusType::Cancelled)
            ->filter(fn (Reservation $reservation) => $this->reservationDate($reservation)?->isFuture())
            ->sortBy(fn (Reservation $reservation) => $this->reservationDate($reservation))
            ->first();

        return Inertia::render('Dashboard', [
            'totals' => [
                'bookings' => Booking::query()->count(),
                'bookingHistory' => $reservations->count(),
                'confirmedBookings' => $reservations->where('status', StatusType::Confirmed)->count(),
            ],
            'statusBreakdown' => [
                'pending' => $reservations->where('status', StatusType::Pending)->count(),
                'confirmed' => $reservations->where('status', StatusType::Confirmed)->count(),
                'cancelled' => $reservations->where('status', StatusType::Cancelled)->count(),
            ],
            'upcomingReservation' => $upcomingReservation
                ? $this->serializeReservation($upcomingReservation)
                : null,
            'recentReservations' => $reservations
                ->take(5)
                ->map(fn (Reservation $reservation) => $this->serializeReservation($reservation))
                ->values(),
        ]);
    }

    private function reservationDate(Reservation $reservation): mixed
    {
        return $reservation->check_in_date ?? $reservation->booking?->event_date;
    }

    private function serializeReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'status' => $reservation->status->value,
            'total_price' => $reservation->total_price,
            'quantity' => $reservation->quantity,
            'created_at' => $reservation->created_at?->toIso8601String(),
            'scheduled_for' => $this->reservationDate($reservation)?->toIso8601String(),
            'booking' => $reservation->booking
                ? [
                    'id' => $reservation->booking->id,
                    'title' => $reservation->booking->title,
                    'location' => $reservation->booking->location,
                    'booking_type' => $reservation->booking->booking_type,
                    'category' => $reservation->booking->category?->name,
                ]
                : null,
        ];
    }
}
