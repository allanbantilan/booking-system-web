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

        $response = $this->post(route('payments.paymaya.checkout'), [
            'booking_id' => $booking->id,
            'quantity' => 2,
        ]);

        // Web flow redirects the browser to PayMaya's hosted checkout.
        $response->assertRedirect('https://example.test/checkout');

        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame(1, Payment::query()->count());

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('CHK-CREATE-1', $payment->checkout_id);
        $this->assertSame('https://example.test/checkout', $payment->checkout_url);

        // Capacity is held at checkout creation (A5): 10 - 2 = 8.
        $this->assertSame(8, $booking->fresh()->capacity);
    }

    public function test_it_releases_hold_when_paymaya_api_fails(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 10);

        Sanctum::actingAs($user);

        $this->mock(PayMayaService::class, function ($mock): void {
            $mock->shouldReceive('createCheckout')
                ->once()
                ->andThrow(new \RuntimeException('PayMaya is down'));
        });

        $response = $this->post(route('payments.paymaya.checkout'), [
            'booking_id' => $booking->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Hold released; reservation and its cascade-linked payment cleaned up.
        $this->assertSame(10, $booking->fresh()->capacity);
        $this->assertSame(0, Reservation::query()->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_it_releases_hold_when_checkout_url_is_missing(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking(capacity: 10);

        Sanctum::actingAs($user);

        $this->mock(PayMayaService::class, function ($mock): void {
            $mock->shouldReceive('createCheckout')
                ->once()
                ->andReturn([
                    'payload' => ['stub' => true],
                    'response' => ['checkoutId' => 'CHK-NO-URL', 'status' => 'CREATED'],
                ]);
        });

        $response = $this->post(route('payments.paymaya.checkout'), [
            'booking_id' => $booking->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Even though the API call succeeded, a missing URL must not leak the hold.
        $this->assertSame(10, $booking->fresh()->capacity);
        $this->assertSame(0, Reservation::query()->count());
        $this->assertSame(0, Payment::query()->count());
    }

    private function makeBooking(int $capacity): Booking
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
}
