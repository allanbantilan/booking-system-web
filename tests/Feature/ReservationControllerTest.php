<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─── store() ────────────────────────────────────────────────────────────

    public function test_store_requires_authentication(): void
    {
        $booking = $this->makeBooking(capacity: 5);

        $this->post(route('reservations.store', $booking->id), ['quantity' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_store_creates_confirmed_reservation_and_decrements_capacity(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 10);

        $this->actingAs($user)
            ->post(route('reservations.store', $booking->id), ['quantity' => 3])
            ->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 3,
            'status' => StatusType::Confirmed->value,
        ]);

        $this->assertSame(7, $booking->fresh()->capacity);
    }

    public function test_store_rejects_quantity_exceeding_capacity(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 2);

        $this->actingAs($user)
            ->post(route('reservations.store', $booking->id), ['quantity' => 5])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseMissing('reservations', ['booking_id' => $booking->id]);
        $this->assertSame(2, $booking->fresh()->capacity);
    }

    public function test_store_rejects_zero_quantity(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 5);

        $this->actingAs($user)
            ->post(route('reservations.store', $booking->id), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');
    }

    public function test_store_returns_404_for_nonexistent_booking(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reservations.store', 99999), ['quantity' => 1])
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
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

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

    public function test_cancel_does_not_restore_capacity_for_pending_reservation(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 10);

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

        // Capacity not changed for pending (no hold yet in current flow)
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
        $booking = $this->makeBooking(capacity: $capacity);

        return Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => $quantity,
            'total_price' => $quantity * 100,
            'status' => StatusType::Confirmed,
        ]);
    }
}
