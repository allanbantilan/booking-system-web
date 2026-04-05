<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payments\PayMayaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayMayaCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_checkout_and_returns_payment(): void
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

        Sanctum::actingAs($user);

        $this->mock(PayMayaService::class, function ($mock): void {
            $mock->shouldReceive('createCheckout')
                ->once()
                ->andReturn([
                    'payload' => ['stub' => true],
                    'response' => [
                        'checkoutId' => 'CHK-CREATE-1',
                        'checkoutUrl' => 'https://example.test/checkout',
                        'status' => 'CREATED',
                    ],
                ]);
        });

        $response = $this->postJson('/api/payments/paymaya/checkout', [
            'booking_id' => $booking->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.checkout_id', 'CHK-CREATE-1')
            ->assertJsonPath('data.checkout_url', 'https://example.test/checkout');

        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame(1, Payment::query()->count());

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('CHK-CREATE-1', $payment->checkout_id);
        $this->assertSame('https://example.test/checkout', $payment->checkout_url);
    }
}
