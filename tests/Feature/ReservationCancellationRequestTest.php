<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BackendUser;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Services\Reservations\ReservationCancellationService;
use App\Types\StatusType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_history_returns_cancellation_request_and_eligibility_payload(): void
    {
        $user = User::factory()->create();
        $eligible = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
        ]);
        $requested = $this->makeReservation($user, [
            'event_date' => now()->addDays(12),
        ]);

        app(ReservationCancellationService::class)
            ->requestCancellation($requested, $user, 'No longer needed');

        $response = $this->actingAs($user)
            ->get(route('bookings.history'));

        $response->assertOk();
        $reservations = collect($response->viewData('page')['props']['reservations'])
            ->keyBy('id');

        $this->assertTrue($reservations[$eligible->id]['cancellation_eligibility']['can_request']);
        $this->assertNull($reservations[$eligible->id]['cancellation_request']);

        $this->assertFalse($reservations[$requested->id]['cancellation_eligibility']['can_request']);
        $this->assertSame('active_request_exists', $reservations[$requested->id]['cancellation_eligibility']['block_reason']);
        $this->assertSame('requested', $reservations[$requested->id]['cancellation_request']['status']);
        $this->assertSame('No longer needed', $reservations[$requested->id]['cancellation_request']['reason']);
    }

    public function test_merchant_approval_cancels_reservation_restores_capacity_and_marks_refund_pending(): void
    {
        $user = User::factory()->create();
        $merchant = BackendUser::create([
            'name' => 'Merchant',
            'email' => 'merchant-review@example.com',
            'password' => 'password',
        ]);
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
            'capacity' => 9,
            'created_by' => $merchant->id,
        ]);

        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, 'Need refund');

        $approved = app(ReservationCancellationService::class)->approve($request, $merchant);

        $this->assertSame(ReservationCancellationRequest::STATUS_APPROVED, $approved->status);
        $this->assertTrue($approved->refund_required);
        $this->assertSame(ReservationCancellationRequest::REFUND_PENDING, $approved->refund_status);
        $this->assertSame(StatusType::Cancelled, $reservation->fresh()->status);
        $this->assertSame(10, $reservation->booking->fresh()->capacity);
    }

    public function test_merchant_rejection_keeps_reservation_active_and_stores_note(): void
    {
        $user = User::factory()->create();
        $merchant = BackendUser::create([
            'name' => 'Merchant',
            'email' => 'merchant-reject@example.com',
            'password' => 'password',
        ]);
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
            'created_by' => $merchant->id,
        ]);
        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, 'Need refund');

        $rejected = app(ReservationCancellationService::class)
            ->reject($request, $merchant, 'Policy does not allow this change.');

        $this->assertSame(ReservationCancellationRequest::STATUS_REJECTED, $rejected->status);
        $this->assertSame('Policy does not allow this change.', $rejected->merchant_note);
        $this->assertSame(StatusType::Confirmed, $reservation->fresh()->status);
    }

    public function test_merchant_cannot_review_another_merchants_request(): void
    {
        $user = User::factory()->create();
        $owner = BackendUser::create([
            'name' => 'Owner',
            'email' => 'merchant-owner@example.com',
            'password' => 'password',
        ]);
        $other = BackendUser::create([
            'name' => 'Other Merchant',
            'email' => 'merchant-other@example.com',
            'password' => 'password',
        ]);
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
            'created_by' => $owner->id,
        ]);
        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, null);

        $this->expectException(AuthorizationException::class);

        app(ReservationCancellationService::class)->approve($request, $other);
    }

    public function test_expired_request_cannot_be_reviewed(): void
    {
        $user = User::factory()->create();
        $merchant = BackendUser::create([
            'name' => 'Merchant',
            'email' => 'merchant-expired@example.com',
            'password' => 'password',
        ]);
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
            'created_by' => $merchant->id,
        ]);
        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, null);
        $request->update(['status' => ReservationCancellationRequest::STATUS_EXPIRED]);

        $this->expectException(ValidationException::class);

        app(ReservationCancellationService::class)->reject($request, $merchant, 'Too late');
    }

    public function test_service_expires_overdue_requests_without_changing_reservation_or_refund(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
        ]);
        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, null);
        $request->update(['expires_at' => now()->subMinute()]);

        $count = app(ReservationCancellationService::class)->expireOverdueRequests();

        $this->assertSame(1, $count);
        $this->assertSame(ReservationCancellationRequest::STATUS_EXPIRED, $request->fresh()->status);
        $this->assertSame(StatusType::Confirmed, $reservation->fresh()->status);
        $this->assertFalse($request->fresh()->refund_required);
        $this->assertSame(ReservationCancellationRequest::REFUND_NOT_REQUIRED, $request->fresh()->refund_status);
    }

    public function test_expiry_command_expires_overdue_requests(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user, [
            'event_date' => now()->addDays(10),
        ]);
        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, null);
        $request->update(['expires_at' => now()->subMinute()]);

        $this->artisan('reservations:expire-cancellation-requests')
            ->expectsOutput('Expired 1 cancellation request(s).')
            ->assertExitCode(0);

        $this->assertSame(ReservationCancellationRequest::STATUS_EXPIRED, $request->fresh()->status);
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
