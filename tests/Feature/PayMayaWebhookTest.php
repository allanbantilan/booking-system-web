<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PayMayaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_requires_token(): void
    {
        $response = $this->postJson('/api/payments/paymaya/webhook', [
            'checkoutId' => 'CHK-WEBHOOK-1',
            'status' => 'PAYMENT_SUCCESS',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_finalizes_payment_when_token_is_valid(): void
    {
        Config::set('services.paymaya.webhook_token', 'test-token');

        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $response = $this->withHeaders(['X-PayMaya-Token' => 'test-token'])
            ->postJson('/api/payments/paymaya/webhook', [
                'checkoutId' => $payment->checkout_id,
                'status' => 'PAYMENT_SUCCESS',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => ['ack' => true],
            ]);

        $payment->refresh();
        $booking->refresh();
        $reservation->refresh();

        $this->assertSame('succeeded', $payment->status);
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
            'checkout_id' => 'CHK-WEBHOOK-1',
            'reference' => 'REF-WEBHOOK-1',
        ]);

        return [$user, $booking, $reservation, $payment];
    }
}
