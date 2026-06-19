<?php

namespace Database\Seeders;

use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Prod-safe demo dataset.
 *
 * Produces a realistic, demo-ready dataset that exercises every screen:
 * backend (CMS) users for each role, three merchants, the full catalogue of
 * merchant-owned bookable listings, frontend customers, and reservations
 * spread across statuses — each confirmed reservation carrying a full
 * Payment -> Receipt money trail.
 *
 * Run explicitly (it never fires from the default `db:seed`):
 *
 *     SEED_DEMO=true php artisan db:seed --class=Database\\Seeders\\DemoSeeder --force
 *
 * It is gated behind SEED_DEMO and fully idempotent (keyed on stable
 * identifiers), so re-running on production is safe.
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Hello123!';

    /** Backend (CMS) users: [name, email, mobile, [roles]]. */
    private const BACKEND_USERS = [
        ['Olivia Bennett', 'superadmin@example.com', '09170000001', ['super_admin', 'admin']],
        ['Liam Carter', 'admin@example.com', '09170000002', ['admin']],
        ['Emma Walker', 'merchant@example.com', '09170000003', ['merchant']],
        ['Mason Rivera', 'merchant2@example.com', '09170000004', ['merchant']],
        ['Isabella Santos', 'merchant3@example.com', '09170000005', ['merchant']],
        ['Noah Parker', 'content@example.com', '09170000006', ['content_manager']],
        ['Ava Mitchell', 'events@example.com', '09170000007', ['event_manager']],
        ['William Brooks', 'usermanager@example.com', '09170000008', ['user_manager']],
        ['Sophia Reed', 'support@example.com', '09170000009', ['support_staff']],
    ];

    private const CUSTOMERS = [
        'James Anderson', 'Mia Thompson', 'Daniel Foster', 'Grace Hughes',
        'Ethan Murphy', 'Chloe Ward', 'Lucas Gray', 'Hannah Cooper',
    ];

    /** Give some categories stay-based types so check-in/out + nights are exercised. */
    private const BOOKING_TYPES_BY_CATEGORY = [
        'Hotels / Accommodations' => Booking::TYPE_ACCOMMODATION,
        'Car Rentals' => Booking::TYPE_RENTAL,
        'Catering Services' => Booking::TYPE_PACKAGE,
        'Spa or Massage Sessions' => Booking::TYPE_SERVICE,
    ];

    /** Status spread per reservation, ~60% confirmed / 20% pending / 20% cancelled. */
    private const STATUS_CYCLE = ['confirmed', 'confirmed', 'confirmed', 'pending', 'cancelled'];

    private const RESERVATIONS_PER_CUSTOMER = 3;

    public function run(): void
    {
        if (! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('DemoSeeder skipped — set SEED_DEMO=true to seed demo data.');

            return;
        }

        // 1. Foundations (idempotent): roles/permissions + categories.
        $this->call([
            RolesAndPermissionsSeeder::class,
            CategoriesSeeder::class,
            CategoryDisplaySeeder::class,
        ]);

        // 2. Backend users — must exist before listings (Booking.created_by).
        $this->seedBackendUsers();

        // 3. Listings — reuse the rich catalogue seeder now that creators exist.
        $this->call(BookingItemsSeeder::class);
        $this->assignBookingTypes();

        // 4. Frontend customers + their reservations with a full money trail.
        $customers = $this->seedCustomers();
        $this->seedReservations($customers);

        $this->command?->info('Demo data seeded.');
    }

    private function seedBackendUsers(): void
    {
        foreach (self::BACKEND_USERS as [$name, $email, $mobile, $roles]) {
            $slug = Str::slug($name);

            $user = BackendUser::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'mobile_number' => $mobile,
                    'facebook_url' => "https://facebook.com/{$slug}",
                    'instagram_url' => "https://instagram.com/{$slug}",
                ]
            );

            $user->syncRoles($roles);
        }
    }

    private function assignBookingTypes(): void
    {
        foreach (self::BOOKING_TYPES_BY_CATEGORY as $categoryName => $type) {
            Booking::query()
                ->whereHas('category', fn ($query) => $query->where('name', $categoryName))
                ->update(['booking_type' => $type]);
        }
    }

    /** @return Collection<int, User> */
    private function seedCustomers(): Collection
    {
        $customers = collect(self::CUSTOMERS)->map(function (string $name): User {
            $email = (string) Str::of($name)->lower()->replace(' ', '.')->append('@example.com');

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['customer']);

            return $user;
        });

        // Real owner account — gets demo reservations and receives transactional
        // email (Resend sandbox only delivers to this address until a domain is verified).
        $owner = User::updateOrCreate(
            ['email' => 'allanbantilan11@gmail.com'],
            [
                'name' => 'Allan Bantilan',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        $owner->syncRoles(['customer']);

        return $customers->push($owner);
    }

    /** @param Collection<int, User> $customers */
    private function seedReservations(Collection $customers): void
    {
        $bookings = Booking::query()->orderBy('id')->get();

        if ($bookings->isEmpty()) {
            return;
        }

        $bookingCount = $bookings->count();
        $index = 0;

        foreach ($customers->values() as $customerIndex => $customer) {
            for ($n = 0; $n < self::RESERVATIONS_PER_CUSTOMER; $n++) {
                $booking = $bookings[($customerIndex * self::RESERVATIONS_PER_CUSTOMER + $n) % $bookingCount];
                $status = self::STATUS_CYCLE[$index % count(self::STATUS_CYCLE)];

                $this->seedReservation($customer, $booking, $status, $index);
                $index++;
            }
        }
    }

    private function seedReservation(User $customer, Booking $booking, string $status, int $index): void
    {
        $defaults = Booking::typeDefaults($booking->booking_type ?? Booking::TYPE_EVENT);
        $requiresNights = (bool) ($defaults['nights_required'] ?? false);

        $quantity = 1 + ($index % 3);
        $nights = $requiresNights ? 1 + ($index % 4) : 1;

        $checkIn = $checkOut = null;
        if ($requiresNights) {
            $checkIn = now()->addDays(5 + $index)->startOfDay();
            $checkOut = $checkIn->copy()->addDays($nights);
        }

        $total = $this->calculateTotal(
            (float) $booking->price,
            $booking->extra_rate !== null ? (float) $booking->extra_rate : null,
            $quantity,
            $nights,
            $requiresNights
        );

        $reservation = Reservation::updateOrCreate(
            ['user_id' => $customer->id, 'booking_id' => $booking->id],
            [
                'quantity' => $quantity,
                'nights' => $nights,
                'total_price' => $total,
                'status' => $status,
                'check_in_date' => $checkIn?->toDateString(),
                'check_out_date' => $checkOut?->toDateString(),
                'cancelled_at' => $status === 'cancelled' ? now()->subDays($index + 1) : null,
            ]
        );

        $this->seedPaymentTrail($reservation, $booking, $status, $total);
    }

    private function seedPaymentTrail(Reservation $reservation, Booking $booking, string $status, float $total): void
    {
        $paymentStatus = match ($status) {
            'confirmed' => 'succeeded',
            'pending' => 'pending',
            default => 'failed',
        };

        $payment = Payment::updateOrCreate(
            ['reservation_id' => $reservation->id],
            [
                'user_id' => $reservation->user_id,
                'provider' => 'paymaya',
                'status' => $paymentStatus,
                'amount' => $total,
                'currency' => 'PHP',
                'checkout_id' => 'demo_chk_' . $reservation->id,
                'checkout_url' => 'https://pg-sandbox.paymaya.com/checkout/demo/' . $reservation->id,
                'reference' => 'DEMO-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT),
                'raw_response' => ['demo' => true, 'status' => $paymentStatus],
            ]
        );

        if ($paymentStatus !== 'succeeded') {
            return;
        }

        // Mirror PaymentFinalizer: receipt issued once, never re-numbered.
        Receipt::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'reservation_id' => $reservation->id,
                'receipt_number' => 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'issued_at' => now(),
                'metadata' => [
                    'reference' => $payment->reference,
                    'customer_name' => $reservation->user?->name,
                    'booking_title' => $booking->title,
                    'booking_date' => $booking->event_date?->toIso8601String(),
                ],
            ]
        );
    }

    /** Mirrors PayMayaCheckoutFlow::calculateTotal so demo totals match real ones. */
    private function calculateTotal(float $basePrice, ?float $extraRate, int $quantity, int $nights, bool $requiresNights): float
    {
        if (! $requiresNights) {
            return $basePrice * $quantity;
        }

        if ($extraRate === null) {
            return $basePrice * $quantity * $nights;
        }

        $extraNights = max(0, $nights - 1);

        return ($basePrice * $quantity) + ($extraRate * $quantity * $extraNights);
    }
}
