<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Services\Reservations\ReservationCancellationService;
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

    public function test_service_requests_cancellation_more_than_three_days_before_event_date(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
            'booking_type' => Booking::TYPE_EVENT,
        ]);

        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, 'Schedule changed');

        $this->assertSame(ReservationCancellationRequest::STATUS_REQUESTED, $request->status);
        $this->assertSame('Schedule changed', $request->reason);
        $this->assertSame(
            $reservation->booking->event_date->copy()->subDays(3)->toDateString(),
            $request->expires_at->toDateString(),
        );
    }

    public function test_service_requests_cancellation_more_than_three_days_before_check_in_date(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => null,
            'booking_type' => Booking::TYPE_ACCOMMODATION,
        ], [
            'check_in_date' => now()->addDays(8)->toDateString(),
            'check_out_date' => now()->addDays(10)->toDateString(),
        ]);

        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, null);

        $this->assertSame(ReservationCancellationRequest::STATUS_REQUESTED, $request->status);
        $this->assertSame(
            $reservation->check_in_date->copy()->subDays(3)->toDateString(),
            $request->expires_at->toDateString(),
        );
    }

    public function test_service_blocks_cancellation_within_three_days_before_start(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(2),
        ]);

        $eligibility = app(ReservationCancellationService::class)->eligibility($reservation);

        $this->assertFalse($eligibility['can_request']);
        $this->assertSame('within_cutoff', $eligibility['block_reason']);
    }

    public function test_service_blocks_cancellation_when_start_date_is_missing(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => null,
            'booking_type' => Booking::TYPE_EVENT,
        ]);

        $eligibility = app(ReservationCancellationService::class)->eligibility($reservation);

        $this->assertFalse($eligibility['can_request']);
        $this->assertSame('missing_booking_date', $eligibility['block_reason']);
    }

    public function test_service_blocks_duplicate_active_cancellation_request(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
        ]);

        app(ReservationCancellationService::class)->requestCancellation($reservation, $user, null);
        $eligibility = app(ReservationCancellationService::class)->eligibility($reservation->fresh());

        $this->assertFalse($eligibility['can_request']);
        $this->assertSame('active_request_exists', $eligibility['block_reason']);
    }

    public function test_customer_can_create_cancellation_request_from_route(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
        ]);

        $this->actingAs($user)
            ->post(route('reservations.cancellation-requests.store', $reservation), [
                'reason' => 'Schedule changed',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Cancellation request submitted for merchant review.');

        $this->assertDatabaseHas('reservation_cancellation_requests', [
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'status' => ReservationCancellationRequest::STATUS_REQUESTED,
            'reason' => 'Schedule changed',
        ]);
    }

    public function test_customer_route_blocks_ineligible_cancellation_request(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(2),
        ]);

        $this->actingAs($user)
            ->post(route('reservations.cancellation-requests.store', $reservation))
            ->assertRedirect()
            ->assertSessionHasErrors(['error' => 'Cancellation is closed within 3 days of booking start.']);

        $this->assertDatabaseMissing('reservation_cancellation_requests', [
            'reservation_id' => $reservation->id,
        ]);
    }

    public function test_customer_cannot_request_cancellation_for_another_users_reservation(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $reservation = $this->makeReservation($owner, [
            'event_date' => now()->addDays(10),
        ]);

        $this->actingAs($attacker)
            ->post(route('reservations.cancellation-requests.store', $reservation))
            ->assertNotFound();
    }

    private function makeReservation(User $user, array $bookingOverrides = [], array $reservationOverrides = []): Reservation
    {
        $booking = Booking::create(array_merge([
            'title' => 'Beach Stay',
            'description' => 'Room booking.',
            'location' => 'Cebu',
            'event_date' => now()->addDays(10),
            'capacity' => 4,
            'price' => 1000,
            'created_by' => null,
            'booking_type' => Booking::TYPE_EVENT,
        ], $bookingOverrides));

        return Reservation::create(array_merge([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 1000,
            'status' => StatusType::Confirmed,
        ], $reservationOverrides));
    }
}
