<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function store(Request $request, int $bookingId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($bookingId, $validated, $user): void {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($bookingId);

            if ($validated['quantity'] > $booking->capacity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Only {$booking->capacity} slots left for this booking."],
                ]);
            }

            $unitPrice = $this->discountedPrice($booking->price, $booking->discount_percentage);

            Reservation::query()->create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'quantity' => $validated['quantity'],
                'total_price' => $unitPrice * $validated['quantity'],
                'status' => 'confirmed',
            ]);

            $booking->update([
                'capacity' => max(0, $booking->capacity - $validated['quantity']),
            ]);
        });

        return back()->with('success', 'Reservation confirmed.');
    }

    public function cancel(Request $request, int $reservationId): RedirectResponse
    {
        DB::transaction(function () use ($reservationId, $request): void {
            $reservation = Reservation::query()
                ->whereKey($reservationId)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            if ($reservation->status === 'confirmed') {
                $booking = Booking::query()
                    ->lockForUpdate()
                    ->find($reservation->booking_id);

                if ($booking) {
                    $booking->update([
                        'capacity' => $booking->capacity + $reservation->quantity,
                    ]);
                }
            }

            $reservation->delete();
        });

        return back()->with('success', 'Reservation cancelled and removed.');
    }

    public function history(Request $request): Response
    {
        $reservations = Reservation::query()
            ->with([
                'booking:id,title,description,location,event_date,capacity,price,discount_percentage,availability_label,quantity_label,meta_line,amenities,category_id',
                'booking.category:id,name,color,badge_label',
                'booking.media',
                'payment',
                'receipt',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return Inertia::render('BookingHistory', [
            'reservations' => $reservations->map(fn (Reservation $reservation) => [
                'id' => $reservation->id,
                'quantity' => $reservation->quantity,
                'total_price' => $reservation->total_price,
                'status' => $reservation->status,
                'payment' => $reservation->payment
                    ? [
                        'id' => $reservation->payment->id,
                        'status' => $reservation->payment->status,
                    ]
                    : null,
                'receipt' => $reservation->receipt
                    ? [
                        'id' => $reservation->receipt->id,
                        'receipt_number' => $reservation->receipt->receipt_number,
                        'issued_at' => $reservation->receipt->issued_at?->toIso8601String(),
                    ]
                    : null,
                'booking' => $reservation->booking
                    ? $this->serializeBooking($reservation->booking)
                    : null,
            ]),
        ]);
    }

    private function discountedPrice(float $price, int $discountPercentage): float
    {
        $discount = max(0, min(100, $discountPercentage));

        return round($price * (1 - ($discount / 100)), 2);
    }

    private function serializeBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'title' => $booking->title,
            'description' => $booking->description,
            'location' => $booking->location,
            'event_date' => $booking->event_date,
            'capacity' => $booking->capacity,
            'price' => $booking->price,
            'discount_percentage' => $booking->discount_percentage,
            'availability_label' => $booking->availability_label,
            'quantity_label' => $booking->quantity_label,
            'meta_line' => $booking->meta_line,
            'amenities' => $booking->amenities,
            'image_urls' => $booking->image_urls,
            'category' => $booking->category
                ? [
                    'id' => $booking->category->id,
                    'name' => $booking->category->name,
                    'color' => $booking->category->color,
                    'badge_label' => $booking->category->badge_label,
                ]
                : null,
        ];
    }
}
