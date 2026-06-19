<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationCompletedTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservation(array $overrides = [], array $bookingOverrides = []): Reservation
    {
        $user = User::factory()->create();
        $booking = Booking::create(array_merge([
            'title' => 'Test Booking',
            'description' => 'Test booking description.',
            'location' => 'Test Location',
            'event_date' => now()->addDay(),
            'capacity' => 10,
            'price' => 100,
            'created_by' => null,
        ], $bookingOverrides));

        return Reservation::create(array_merge([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 100,
            'status' => StatusType::Confirmed,
            'check_in_date' => now()->subDays(3),
            'check_out_date' => now()->subDay(),
        ], $overrides));
    }

    public function test_confirmed_past_checkout_is_completed(): void
    {
        $r = $this->makeReservation();
        $this->assertTrue($r->isCompleted());
        $this->assertSame('Completed', $r->status_label);
    }

    public function test_confirmed_future_checkout_is_not_completed(): void
    {
        $r = $this->makeReservation(['check_out_date' => now()->addDays(2)]);
        $this->assertFalse($r->isCompleted());
        $this->assertSame('Confirmed', $r->status_label);
    }

    public function test_null_checkout_falls_back_to_booking_event_date(): void
    {
        $r = $this->makeReservation(
            ['check_in_date' => null, 'check_out_date' => null],
            ['event_date' => now()->subDay()],
        );
        $this->assertTrue($r->isCompleted());
    }

    public function test_pending_past_checkout_is_not_completed(): void
    {
        $r = $this->makeReservation(['status' => StatusType::Pending]);
        $this->assertFalse($r->isCompleted());
    }

    public function test_cancelled_is_not_completed(): void
    {
        $r = $this->makeReservation(['status' => StatusType::Cancelled]);
        $this->assertFalse($r->isCompleted());
        $this->assertSame('Cancelled', $r->status_label);
    }
}
