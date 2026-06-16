<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationCancellationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancellation_request_belongs_to_reservation_user_and_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Beach Stay',
            'description' => 'Room booking.',
            'location' => 'Cebu',
            'event_date' => now()->addDays(10),
            'capacity' => 5,
            'price' => 1000,
            'created_by' => null,
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 1000,
            'status' => StatusType::Confirmed,
        ]);

        $request = ReservationCancellationRequest::create([
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'status' => 'requested',
            'reason' => 'Schedule changed',
            'requested_at' => now(),
            'expires_at' => now()->addDays(7),
            'refund_status' => 'not_required',
        ]);

        $this->assertTrue($request->reservation->is($reservation));
        $this->assertTrue($request->user->is($user));
        $this->assertTrue($request->booking->is($booking));
        $this->assertTrue($reservation->cancellationRequests->first()->is($request));
        $this->assertTrue($reservation->activeCancellationRequest->is($request));
    }
}
