<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PayMayaReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_route_uses_web_guard_not_sanctum(): void
    {
        // Regression: sanctum ignores the session cookie when the Referer is the
        // Maya domain, which logged returning users out. The web guard does not.
        $middleware = Route::getRoutes()->getByName('payments.paymaya.return')->gatherMiddleware();

        $this->assertContains('auth:web', $middleware);
        $this->assertNotContains('auth:sanctum', $middleware);
    }

    public function test_return_rejects_checkout_id_mismatch(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $response = $this->actingAs($user)->get(
            '/payments/paymaya/return?payment_id=' . $payment->id . '&status=success&checkoutId=CHK-OTHER'
        );

        $response->assertOk();

        $payment->refresh();
        $reservation->refresh();
        $booking->refresh();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('pending', $reservation->status->value);
        $this->assertSame(7, $booking->capacity);
        $this->assertNull($payment->receipt);
    }

    public function test_return_finalizes_payment_for_owner_and_matching_checkout(): void
    {
        [$user, $booking, $reservation, $payment] = $this->createPaymentScenario();

        $response = $this->actingAs($user)->get(
            '/payments/paymaya/return?payment_id=' . $payment->id . '&status=success&checkoutId=' . $payment->checkout_id
        );

        $response->assertOk();

        $payment->refresh();
        $reservation->refresh();
        $booking->refresh();

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
            // Capacity is 7: 10 original minus 3 held at checkout (A5 fix).
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
            'checkout_id' => 'CHK-RETURN-1',
            'reference' => 'REF-RETURN-1',
        ]);

        return [$user, $booking, $reservation, $payment];
    }
}
