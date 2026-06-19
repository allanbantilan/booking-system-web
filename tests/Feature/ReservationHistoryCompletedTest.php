<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReservationHistoryCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_marks_past_checkout_reservation_completed(): void
    {
        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Past Stay',
            'description' => 'desc',
            'location' => 'loc',
            'event_date' => now()->subDays(5),
            'capacity' => 5,
            'price' => 100,
            'created_by' => null,
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 100,
            'status' => StatusType::Confirmed,
            'check_in_date' => now()->subDays(3),
            'check_out_date' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get('/bookings/history')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('BookingHistory')
                ->where('reservations.0.is_completed', true)
            );
    }
}
