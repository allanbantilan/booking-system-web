<?php

namespace Tests\Feature;

use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payments\PayMayaService;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_customer_and_admin_surfaces_render(): void
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $booking = $this->makeBooking();
        $reservation = Reservation::create([
            'user_id' => $customer->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 1500,
            'status' => StatusType::Confirmed,
        ]);
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'user_id' => $customer->id,
            'provider' => 'paymaya',
            'status' => 'succeeded',
            'amount' => 1500,
            'currency' => 'PHP',
            'reference' => 'PMY-SMOKE-1',
        ]);
        Receipt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'receipt_number' => 'RCPT-SMOKE-1',
            'amount' => 1500,
            'currency' => 'PHP',
            'issued_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home'));
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('totals.bookings', 1)
                ->where('totals.bookingHistory', 1));
        $this->actingAs($customer)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Bookings')->has('bookings.data', 1));
        $this->actingAs($customer)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('BookingShow')->where('booking.id', $booking->id));
        $this->actingAs($customer)
            ->get(route('bookings.history'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('BookingHistory')->has('reservations', 1));
    }

    public function test_admin_panel_surfaces_render(): void
    {
        $backend = BackendUser::create([
            'name' => 'Admin Smoke',
            'email' => 'admin-smoke@example.com',
            'password' => 'password',
        ]);

        $this->get('/admin/login')->assertOk();
        $this->actingAs($backend, 'backend')
            ->get('/admin')
            ->assertOk();
    }

    public function test_payment_webhook_and_history_happy_path(): void
    {
        config(['services.paymaya.webhook_token' => 'smoke-token']);

        $customer = User::factory()->create(['email_verified_at' => now()]);
        $booking = $this->makeBooking(['capacity' => 3]);

        Sanctum::actingAs($customer);

        $this->mock(PayMayaService::class, function ($mock): void {
            $mock->shouldReceive('createCheckout')
                ->once()
                ->andReturn([
                    'payload' => ['smoke' => true],
                    'response' => [
                        'checkoutId' => 'CHK-SMOKE-1',
                        'checkoutUrl' => 'https://example.test/checkout',
                        'status' => 'CREATED',
                    ],
                ]);
        });

        $this->post(route('payments.paymaya.checkout'), [
            'booking_id' => $booking->id,
            'quantity' => 2,
        ])->assertRedirect('https://example.test/checkout');

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('CHK-SMOKE-1', $payment->checkout_id);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(1, $booking->fresh()->capacity);

        $this->postJson(route('api.payments.paymaya.webhook'), [
            'checkoutId' => 'CHK-SMOKE-1',
            'status' => 'PAYMENT_SUCCESS',
            'transactionReferenceNo' => 'TXN-SMOKE-1',
        ], [
            'X-PayMaya-Token' => 'smoke-token',
        ])->assertOk();

        $payment->refresh();
        $reservation = $payment->reservation()->firstOrFail();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(StatusType::Confirmed, $reservation->status);
        $this->assertNotNull($payment->receipt);
        $this->assertSame('TXN-SMOKE-1', $payment->raw_webhook['transactionReferenceNo']);

        $this->actingAs($customer)
            ->get(route('bookings.history'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BookingHistory')
                ->where('reservations.0.payment.status', 'succeeded')
                ->has('reservations.0.receipt'));
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'title' => 'Deployment Smoke Booking',
            'description' => 'Smoke test booking description.',
            'location' => 'Cebu',
            'event_date' => now()->addDays(10),
            'capacity' => 10,
            'price' => 750,
            'created_by' => null,
        ], $overrides));
    }
}
