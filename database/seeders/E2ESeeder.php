<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\User;
use App\Types\StatusType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate so re-seeding restores the password the reset test changed.
        User::updateOrCreate(
            ['email' => 'e2e@example.com'],
            ['name' => 'Evan Tester', 'password' => Hash::make('password')],
        );

        $badgeUser = User::updateOrCreate(
            ['email' => 'badge@example.com'],
            ['name' => 'Bella Badge', 'password' => Hash::make('password')],
        );

        // Idempotent: only create the demo booking+reservation once.
        $booking = Booking::firstOrCreate(
            ['title' => 'E2E Completed Stay'],
            [
                'description' => 'A booking whose checkout is in the past.',
                'location' => 'Test City',
                'event_date' => now()->subWeek(),
                'capacity' => 5,
                'price' => 120,
                'created_by' => null,
            ],
        );

        Reservation::firstOrCreate(
            ['user_id' => $badgeUser->id, 'booking_id' => $booking->id],
            [
                'quantity' => 1,
                'total_price' => 120,
                'status' => StatusType::Confirmed,
                'check_in_date' => now()->subDays(3),
                'check_out_date' => now()->subDay(),
            ],
        );
    }
}
