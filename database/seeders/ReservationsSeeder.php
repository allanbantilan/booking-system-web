<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationsSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::query()->get();
        $users = User::query()->get();

        if ($bookings->isEmpty() || $users->isEmpty()) {
            return;
        }

        $statusCycle = ['confirmed', 'pending'];
        $bookingCount = $bookings->count();

        foreach ($users as $userIndex => $user) {
            $pickedBookings = $bookings
                ->shuffle()
                ->take(min(2, $bookingCount));

            foreach ($pickedBookings as $bookingIndex => $booking) {
                $quantity = 1 + (($userIndex + $bookingIndex) % 3);
                $status = $statusCycle[($userIndex + $bookingIndex) % count($statusCycle)];

                Reservation::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'total_price' => $booking->price * $quantity,
                        'status' => $status,
                    ]
                );
            }
        }
    }
}
