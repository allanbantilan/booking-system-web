<?php

namespace Tests\Feature;

use App\Models\BackendUser;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('SEED_DEMO');
        unset($_ENV['SEED_DEMO'], $_SERVER['SEED_DEMO']);

        parent::tearDown();
    }

    private function enableDemoFlag(): void
    {
        putenv('SEED_DEMO=true');
        $_ENV['SEED_DEMO'] = 'true';
        $_SERVER['SEED_DEMO'] = 'true';
    }

    public function test_it_no_ops_without_the_flag(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(0, BackendUser::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Reservation::count());
    }

    public function test_it_seeds_a_full_demo_dataset(): void
    {
        $this->enableDemoFlag();

        $this->seed(DemoSeeder::class);

        // One backend user per role, including the merchant.
        $this->assertSame(7, BackendUser::count());
        $this->assertTrue(
            BackendUser::where('email', 'merchant@example.com')->first()?->hasRole('merchant') ?? false
        );

        // Eight customers, each holding the customer role.
        $this->assertSame(8, User::count());
        $this->assertTrue(User::first()->hasRole('customer'));

        // 8 customers x 3 reservations.
        $this->assertSame(24, Reservation::count());

        // Confirmed reservations carry a succeeded payment + a receipt.
        $confirmed = Reservation::where('status', 'confirmed')->with('payment', 'receipt')->get();
        $this->assertNotEmpty($confirmed);
        foreach ($confirmed as $reservation) {
            $this->assertSame('succeeded', $reservation->payment->status);
            $this->assertNotNull($reservation->receipt);
            $this->assertSame((string) $reservation->total_price, (string) $reservation->payment->amount);
        }

        // Pending reservations: pending payment, no receipt.
        $pending = Reservation::where('status', 'pending')->with('payment', 'receipt')->get();
        $this->assertNotEmpty($pending);
        foreach ($pending as $reservation) {
            $this->assertSame('pending', $reservation->payment->status);
            $this->assertNull($reservation->receipt);
        }

        // Cancelled reservations: cancelled_at set, failed payment, no receipt.
        $cancelled = Reservation::where('status', 'cancelled')->with('payment', 'receipt')->get();
        $this->assertNotEmpty($cancelled);
        foreach ($cancelled as $reservation) {
            $this->assertNotNull($reservation->cancelled_at);
            $this->assertSame('failed', $reservation->payment->status);
            $this->assertNull($reservation->receipt);
        }
    }

    public function test_it_is_idempotent(): void
    {
        $this->enableDemoFlag();

        $this->seed(DemoSeeder::class);
        $reservations = Reservation::count();
        $payments = Payment::count();
        $receipts = Receipt::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame(7, BackendUser::count());
        $this->assertSame(8, User::count());
        $this->assertSame($reservations, Reservation::count());
        $this->assertSame($payments, Payment::count());
        $this->assertSame($receipts, Receipt::count());
    }
}
