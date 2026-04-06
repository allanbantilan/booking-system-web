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

class PayMayaCheckoutDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_requires_date_range(): void
    {
        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Rental Booking',
            'description' => 'Rental booking description.',
            'location' => 'Test Location',
            'event_date' => now()->addDay(),
            'capacity' => 10,
            'price' => 100,
            'created_by' => null,
            'booking_type' => Booking::TYPE_RENTAL,
        ]);

        Sanctum::actingAs($user);

        $this->mock(PayMayaService::class, function ($mock): void {
            $mock->shouldReceive('createCheckout')->never();
        });

        $response = $this->postJson('/api/payments/paymaya/checkout', [
            'booking_id' => $booking->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in_date', 'check_out_date']);
    }

    public function test_accommodation_dates_compute_stay_length_and_total_price(): void
    {
        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Accommodation Booking',
            'description' => 'Accommodation booking description.',
            'location' => 'Test Location',
            'event_date' => now()->addDay(),
            'capacity' => 10,
            'price' => 100,
            'extra_rate' => 50,
            'created_by' => null,
            'booking_type' => Booking::TYPE_ACCOMMODATION,
        ]);

        Sanctum::actingAs($user);

        $this->mock(PayMayaService::class, function ($mock): void {
            $mock->shouldReceive('createCheckout')
                ->once()
                ->andReturn([
                    'payload' => ['stub' => true],
                    'response' => [
                        'checkoutId' => 'CHK-DATE-1',
                        'checkoutUrl' => 'https://example.test/checkout',
                        'status' => 'CREATED',
                    ],
                ]);
        });

        $response = $this->postJson('/api/payments/paymaya/checkout', [
            'booking_id' => $booking->id,
            'quantity' => 2,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.checkout_id', 'CHK-DATE-1');

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame(3, $reservation->nights);
        $this->assertSame(now()->toDateString(), $reservation->check_in_date->toDateString());
        $this->assertSame(now()->addDays(3)->toDateString(), $reservation->check_out_date->toDateString());
        $this->assertSame('400.00', $reservation->total_price);

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('pending', $payment->status);
    }
}
