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

    public function test_it_confirms_reservation_decrements_capacity_and_creates_receipt_once(): void
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
        $this->assertSame(7, $booking->capacity);
        $this->assertNotNull($payment->receipt);

        $finalizer->apply($payment, 'succeeded', ['status' => 'PAYMENT_SUCCESS']);

        $booking->refresh();
        $reservation->refresh();
        $payment->refresh();

        $this->assertSame(7, $booking->capacity);
        $this->assertSame('confirmed', $reservation->status->value);
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
            'capacity' => 10,
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
