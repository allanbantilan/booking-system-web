<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─── store() route removed (A1) ─────────────────────────────────────────
    // The direct-reserve route allowed any authenticated user to create a
    // confirmed reservation with zero payment. The route has been removed;
    // all reservations must go through the PayMaya checkout flow.

    public function test_direct_reserve_route_is_gone(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 5);

        $this->actingAs($user)
            ->post("/bookings/{$booking->id}/reserve", ['quantity' => 1])
            ->assertNotFound();
    }

    // ─── cancel() ───────────────────────────────────────────────────────────

    public function test_cancel_requires_authentication(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeConfirmedReservation($user, capacity: 10, quantity: 2);

        $this->patch(route('reservations.cancel', $reservation->id))
            ->assertRedirect(route('login'));
    }

    public function test_cancel_marks_reservation_as_cancelled_and_restores_capacity(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeConfirmedReservation($user, capacity: 10, quantity: 3);
        $booking = $reservation->booking;

        $this->actingAs($user)
            ->patch(route('reservations.cancel', $reservation->id))
            ->assertRedirect();

        // Reservation must exist with cancelled status (not deleted)
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
        ]);

        // Capacity must be restored
        $this->assertSame(10, $booking->fresh()->capacity);
    }

    public function test_cancel_prevents_cancellation_after_three_day_window(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 10);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 2,
            'total_price' => 200,
            'status' => StatusType::Confirmed,
        ]);

        // Force created_at to 4 days ago — Eloquent ignores it in create() for timestamps.
        \Illuminate\Support\Facades\DB::table('reservations')
            ->where('id', $reservation->id)
            ->update(['created_at' => now()->subDays(4)]);
        $reservation->refresh();

        $this->actingAs($user)
            ->patch(route('reservations.cancel', $reservation->id))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => StatusType::Confirmed->value,
        ]);
    }

    public function test_cancel_prevents_cancelling_another_users_reservation(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $reservation = $this->makeConfirmedReservation($owner, capacity: 10, quantity: 2);

        $this->actingAs($attacker)
            ->patch(route('reservations.cancel', $reservation->id))
            ->assertNotFound();

        // Reservation untouched
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => StatusType::Confirmed->value,
        ]);
    }

    public function test_cancel_prevents_cancellation_when_receipt_was_issued(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeConfirmedReservation($user, capacity: 10, quantity: 2);
        $payment = $this->makePayment($reservation, 'paid');

        Receipt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'receipt_number' => 'RCT-ISSUED-1',
            'amount' => $reservation->total_price,
            'currency' => 'PHP',
            'issued_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('reservations.cancel', $reservation->id))
            ->assertSessionHasErrors(['error' => 'Receipt issued.']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => StatusType::Confirmed->value,
        ]);
        $this->assertSame(8, $reservation->booking->fresh()->capacity);
    }

    public function test_cancel_restores_capacity_for_pending_reservation(): void
    {
        $user = User::factory()->create();
        // Capacity is 7: 10 original minus 3 held at checkout (A5).
        $booking = $this->makeBooking(capacity: 7);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 3,
            'total_price' => 300,
            'status' => StatusType::Pending,
        ]);

        $this->actingAs($user)
            ->patch(route('reservations.cancel', $reservation->id))
            ->assertRedirect();

        // Capacity restored for pending since A5 holds capacity at checkout.
        $this->assertSame(10, $booking->fresh()->capacity);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
        ]);
    }

    // ─── history() ──────────────────────────────────────────────────────────

    public function test_history_requires_authentication(): void
    {
        $this->get(route('bookings.history'))
            ->assertRedirect(route('login'));
    }

    public function test_history_returns_only_authenticated_users_reservations(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->makeConfirmedReservation($user, capacity: 10, quantity: 1);
        $this->makeConfirmedReservation($other, capacity: 10, quantity: 1);

        $response = $this->actingAs($user)
            ->get(route('bookings.history'));

        $response->assertOk();
        $reservations = $response->viewData('page')['props']['reservations'];

        $this->assertCount(1, $reservations);
        $this->assertSame($mine->id, $reservations[0]['id']);
    }

    public function test_history_returns_cancellation_contract_for_blocked_reservations(): void
    {
        $user = User::factory()->create();
        $cancelled = $this->makeConfirmedReservation($user, capacity: 10, quantity: 1);
        $cancelled->update([
            'status' => StatusType::Cancelled,
            'cancelled_at' => now(),
        ]);

        $receiptBlocked = $this->makeConfirmedReservation($user, capacity: 10, quantity: 1);
        $payment = $this->makePayment($receiptBlocked, 'paid');
        Receipt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $receiptBlocked->id,
            'receipt_number' => 'RCT-HISTORY-1',
            'amount' => $receiptBlocked->total_price,
            'currency' => 'PHP',
            'issued_at' => now(),
        ]);

        $outsideWindow = $this->makeConfirmedReservation($user, capacity: 10, quantity: 1);
        \Illuminate\Support\Facades\DB::table('reservations')
            ->where('id', $outsideWindow->id)
            ->update(['created_at' => now()->subDays(4)]);

        $response = $this->actingAs($user)
            ->get(route('bookings.history'));

        $response->assertOk();
        $reservations = collect($response->viewData('page')['props']['reservations'])
            ->keyBy('id');

        $this->assertSame('already_cancelled', $reservations[$cancelled->id]['cancel_block_reason']);
        $this->assertSame('Already cancelled', $reservations[$cancelled->id]['cancel_block_label']);
        $this->assertSame('receipt_issued', $reservations[$receiptBlocked->id]['cancel_block_reason']);
        $this->assertSame('Receipt issued', $reservations[$receiptBlocked->id]['cancel_block_label']);
        $this->assertSame('outside_window', $reservations[$outsideWindow->id]['cancel_block_reason']);
        $this->assertSame('Cancellation window ended', $reservations[$outsideWindow->id]['cancel_block_label']);
        $this->assertSame('Cancellable within 3 days before receipt is issued.', $reservations[$outsideWindow->id]['cancel_policy_label']);
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    private function makeBooking(int $capacity = 10): Booking
    {
        return Booking::create([
            'title' => 'Test Booking',
            'description' => 'Test booking description.',
            'location' => 'Test Location',
            'event_date' => now()->addDay(),
            'capacity' => $capacity,
            'price' => 100,
            'created_by' => null,
        ]);
    }

    private function makeConfirmedReservation(User $user, int $capacity, int $quantity): Reservation
    {
        // Capacity starts at ($capacity - $quantity) — the checkout hold (A5) already
        // decremented it. Cancel should restore it back to $capacity.
        $booking = $this->makeBooking(capacity: $capacity - $quantity);

        return Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => $quantity,
            'total_price' => $quantity * 100,
            'status' => StatusType::Confirmed,
        ]);
    }

    private function makePayment(Reservation $reservation, string $status): Payment
    {
        return Payment::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'provider' => 'paymaya',
            'status' => $status,
            'amount' => $reservation->total_price,
            'currency' => 'PHP',
            'reference' => 'PMY-TEST-' . $reservation->id,
        ]);
    }
}
