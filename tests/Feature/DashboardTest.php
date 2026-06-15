<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_activity_is_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking = Booking::query()->create([
            'title' => 'Upcoming stay',
            'location' => 'Makati',
            'event_date' => now()->addWeek(),
            'capacity' => 2,
            'price' => 1000,
        ]);

        Reservation::query()->create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 1000,
            'status' => 'confirmed',
        ]);
        Reservation::query()->create([
            'user_id' => $otherUser->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 1000,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('statusBreakdown.confirmed', 1)
                ->where('statusBreakdown.pending', 0)
                ->has('upcomingReservation')
                ->has('recentReservations', 1));
    }
}
