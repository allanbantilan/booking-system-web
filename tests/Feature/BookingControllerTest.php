<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_search_matches_title_location_and_description(): void
    {
        $user = User::factory()->create();

        $titleMatch = $this->makeBooking([
            'title' => 'Sunset Kayak Tour',
            'location' => 'Harbor',
            'description' => 'Paddle route.',
        ]);
        $locationMatch = $this->makeBooking([
            'title' => 'Private Room',
            'location' => 'Sunset Villas',
            'description' => 'Quiet stay.',
        ]);
        $descriptionMatch = $this->makeBooking([
            'title' => 'Weekend Package',
            'location' => 'Town Center',
            'description' => 'Includes sunset dinner.',
        ]);
        $this->makeBooking([
            'title' => 'City Workshop',
            'location' => 'Studio',
            'description' => 'Hands-on session.',
        ]);

        $response = $this->actingAs($user)
            ->get(route('bookings.index', ['search' => 'sunset']));

        $response->assertOk();

        $bookings = collect($response->viewData('page')['props']['bookings']['data']);

        $this->assertSame(
            [$titleMatch->id, $locationMatch->id, $descriptionMatch->id],
            $bookings->pluck('id')->all(),
        );
        $this->assertSame('sunset', $response->viewData('page')['props']['filters']['search']);
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'title' => 'Test Booking',
            'description' => 'Test booking description.',
            'location' => 'Test Location',
            'event_date' => now()->addDay(),
            'capacity' => 10,
            'price' => 100,
            'created_by' => null,
        ], $overrides));
    }
}
