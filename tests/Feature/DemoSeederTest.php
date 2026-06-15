<?php

namespace Tests\Feature;

use App\Models\BackendUser;
use App\Models\Booking;
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
        putenv('SEED_EXTERNAL_IMAGES');
        unset(
            $_ENV['SEED_DEMO'],
            $_SERVER['SEED_DEMO'],
            $_ENV['SEED_EXTERNAL_IMAGES'],
            $_SERVER['SEED_EXTERNAL_IMAGES'],
        );

        parent::tearDown();
    }

    private function enableDemoFlag(): void
    {
        putenv('SEED_DEMO=true');
        putenv('SEED_EXTERNAL_IMAGES=false');
        $_ENV['SEED_DEMO'] = 'true';
        $_SERVER['SEED_DEMO'] = 'true';
        $_ENV['SEED_EXTERNAL_IMAGES'] = 'false';
        $_SERVER['SEED_EXTERNAL_IMAGES'] = 'false';
    }

    public function test_it_no_ops_without_the_flag(): void
    {
        putenv('SEED_DEMO=false');
        $_ENV['SEED_DEMO'] = 'false';
        $_SERVER['SEED_DEMO'] = 'false';

        $backendUsers = BackendUser::count();
        $users = User::count();
        $reservations = Reservation::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame($backendUsers, BackendUser::count());
        $this->assertSame($users, User::count());
        $this->assertSame($reservations, Reservation::count());
    }

    public function test_it_seeds_a_full_demo_dataset(): void
    {
        $this->enableDemoFlag();

        $this->seed(DemoSeeder::class);

        // One backend user per non-merchant role plus three merchants.
        $this->assertGreaterThanOrEqual(9, BackendUser::count());

        $merchants = BackendUser::role('merchant', 'backend')->orderBy('id')->get();
        $this->assertCount(3, $merchants);
        $this->assertSame(
            ['merchant@example.com', 'merchant2@example.com', 'merchant3@example.com'],
            $merchants->pluck('email')->all()
        );

        // Every listing belongs to a merchant, distributed across all three.
        $this->assertGreaterThan(0, Booking::count());
        $this->assertSame(
            Booking::count(),
            Booking::whereIn('created_by', $merchants->pluck('id'))->count()
        );
        foreach ($merchants as $merchant) {
            $this->assertTrue(Booking::where('created_by', $merchant->id)->exists());
        }

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
        $backendUsers = BackendUser::count();

        Booking::query()->first()->update([
            'created_by' => BackendUser::where('email', 'admin@example.com')->value('id'),
        ]);

        $this->seed(DemoSeeder::class);

        $merchantIds = BackendUser::role('merchant', 'backend')->pluck('id');

        $this->assertSame($backendUsers, BackendUser::count());
        $this->assertCount(3, $merchantIds);
        $this->assertSame(Booking::count(), Booking::whereIn('created_by', $merchantIds)->count());
        $this->assertSame(8, User::count());
        $this->assertSame($reservations, Reservation::count());
        $this->assertSame($payments, Payment::count());
        $this->assertSame($receipts, Receipt::count());
    }
}
