<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payments\PayMayaService;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayMayaCheckoutStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_checkout_status_and_finalizes_payment(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        Sanctum::actingAs($user);

        $this->mock(PayMayaService::class, function ($mock) use ($payment): void {
            $mock->shouldReceive('fetchCheckout')
                ->once()
                ->with($payment->checkout_id)
                ->andReturn(['status' => 'PAYMENT_SUCCESS']);
        });

        $response = $this->getJson('/api/payments/paymaya/checkout/' . $payment->checkout_id);

        $response->assertOk();

        $payment->refresh();
        $booking->refresh();
        $reservation->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(['status' => 'PAYMENT_SUCCESS'], $payment->raw_response);
        $this->assertSame('confirmed', $reservation->status->value);
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
            'checkout_id' => 'CHK-STATUS-1',
            'reference' => 'REF-STATUS-1',
        ]);

        return [$user, $booking, $reservation, $payment];
    }
}
