<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payments\PaymentFinalizer;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFinalizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_reservation_and_creates_receipt_once(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $finalizer = app(PaymentFinalizer::class);

        $finalizer->apply($payment, 'succeeded', ['status' => 'PAYMENT_SUCCESS']);

        $payment->refresh();
        $booking->refresh();
        $reservation->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(['status' => 'PAYMENT_SUCCESS'], $payment->raw_response);
        $this->assertSame('confirmed', $reservation->status->value);
        // Capacity was already held at checkout (A5) — finalizer must not change it.
        $this->assertSame(7, $booking->capacity);
        $this->assertNotNull($payment->receipt);
    }

    public function test_it_is_idempotent_on_duplicate_apply(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $finalizer = app(PaymentFinalizer::class);

        $finalizer->apply($payment, 'succeeded', ['status' => 'PAYMENT_SUCCESS']);
        $finalizer->apply($payment, 'succeeded', ['status' => 'PAYMENT_SUCCESS']);

        $booking->refresh();
        $reservation->refresh();
        $payment->refresh();

        // Capacity unchanged after second call.
        $this->assertSame(7, $booking->capacity);
        $this->assertSame('confirmed', $reservation->status->value);
        $this->assertCount(1, $payment->receipt()->get());
    }

    public function test_it_releases_capacity_hold_on_failure(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $finalizer = app(PaymentFinalizer::class);

        $finalizer->apply($payment, 'failed');

        $payment->refresh();
        $reservation->refresh();
        $booking->refresh();

        $this->assertSame('failed', $payment->status);
        // Reservation cancelled and the checkout hold (3 slots) released back.
        $this->assertSame(StatusType::Cancelled->value, $reservation->status->value);
        $this->assertSame(10, $booking->capacity);
    }

    public function test_failure_release_is_idempotent(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $finalizer = app(PaymentFinalizer::class);

        $finalizer->apply($payment, 'failed');
        $finalizer->apply($payment, 'cancelled');

        $booking->refresh();

        // Hold released exactly once despite two terminal webhooks.
        $this->assertSame(10, $booking->capacity);
    }

    public function test_success_after_failure_retakes_the_hold(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $finalizer = app(PaymentFinalizer::class);

        // Out-of-order webhooks: failed first (releases hold), then succeeded.
        $finalizer->apply($payment, 'failed');
        $finalizer->apply($payment, 'succeeded', ['status' => 'PAYMENT_SUCCESS']);

        $payment->refresh();
        $reservation->refresh();
        $booking->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(StatusType::Confirmed->value, $reservation->status->value);
        // Hold re-taken so capacity is back to the held value, not leaked.
        $this->assertSame(7, $booking->capacity);
        $this->assertNotNull($payment->receipt);
    }

    private function createPaymentScenario(): array
    {
        $user = User::factory()->create();

        $booking = Booking::create([
            'title' => 'Test Booking',
            'description' => 'Test booking description.',
            'location' => 'Test Location',
            'event_date' => now()->addDay(),
            // Capacity is 7: 10 original slots minus the 3 held at checkout (A5).
            'capacity' => 7,
            'price' => 100,
            'created_by' => null,
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 3,
            'total_price' => 300,
            'status' => StatusType::Pending,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'provider' => 'paymaya',
            'status' => 'pending',
            'amount' => 300,
            'currency' => 'PHP',
            'checkout_id' => 'CHK-UNIT-1',
            'reference' => 'REF-UNIT-1',
        ]);

        return [$user, $booking, $reservation, $payment];
    }
}
