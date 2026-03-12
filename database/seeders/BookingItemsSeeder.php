<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingItemsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->first();

        if (! $admin) {
            return;
        }

        $now = now();

        $templates = [
            'Flights' => [
                'title' => 'Manila to Cebu Roundtrip Seat',
                'description' => 'Secure an economy seat with free carry-on and flexible rebooking. Ideal for weekend getaways and business trips.',
                'location' => 'NAIA Terminal 3, Pasay',
                'capacity' => 180,
                'price' => 3499,
                'discount_percentage' => 10,
                'queries' => ['airplane', 'airport'],
            ],
            'Hotels / Accommodations' => [
                'title' => 'Boracay Beachfront King Room',
                'description' => 'One-night stay with breakfast and ocean view. Check-in after 2 PM, late checkout available.',
                'location' => 'White Beach, Boracay',
                'capacity' => 2,
                'price' => 6800,
                'discount_percentage' => 10,
                'queries' => ['hotel room', 'resort'],
            ],
            'Car Rentals' => [
                'title' => 'Compact Car Rental (24 Hours)',
                'description' => 'Fuel-efficient compact car with automatic transmission. Includes basic insurance and unlimited mileage.',
                'location' => 'Makati CBD',
                'capacity' => 5,
                'price' => 2200,
                'discount_percentage' => 10,
                'queries' => ['car rental', 'compact car'],
            ],
            'Restaurant Tables' => [
                'title' => 'Chef\'s Tasting Table for Two',
                'description' => 'Reserve a prime dinner slot with a curated six-course tasting menu.',
                'location' => 'Poblacion, Makati',
                'capacity' => 2,
                'price' => 3200,
                'discount_percentage' => 10,
                'queries' => ['restaurant', 'fine dining'],
            ],
            'Private Dining Rooms' => [
                'title' => 'Private Dining Room (10 Guests)',
                'description' => 'Exclusive room with dedicated server and customizable menu packages.',
                'location' => 'BGC, Taguig',
                'capacity' => 10,
                'price' => 9500,
                'discount_percentage' => 10,
                'queries' => ['private dining', 'banquet room'],
            ],
            'Catering Services' => [
                'title' => 'Corporate Catering Package',
                'description' => 'Buffet setup with appetizers, mains, and desserts for small corporate events.',
                'location' => 'Ortigas Center, Pasig',
                'capacity' => 30,
                'price' => 18000,
                'discount_percentage' => 10,
                'queries' => ['catering', 'buffet'],
            ],
            'Concert Tickets' => [
                'title' => 'Arena Concert Lower Box',
                'description' => 'Lower box seat for a live concert with clear stage visibility.',
                'location' => 'Smart Araneta Coliseum, QC',
                'capacity' => 1,
                'price' => 4800,
                'discount_percentage' => 10,
                'queries' => ['concert', 'arena'],
            ],
            'Movies (Cinemas)' => [
                'title' => 'Cinema Luxe Recliner Seats',
                'description' => 'Reserve two recliner seats with in-seat dining service.',
                'location' => 'Uptown Mall, BGC',
                'capacity' => 2,
                'price' => 1400,
                'discount_percentage' => 10,
                'queries' => ['cinema', 'movie theater'],
            ],
            'Sports Games' => [
                'title' => 'Courtside Sports Pass',
                'description' => 'Courtside ticket for a pro league game with VIP entry.',
                'location' => 'MOA Arena, Pasay',
                'capacity' => 1,
                'price' => 5200,
                'discount_percentage' => 10,
                'queries' => ['stadium', 'basketball game'],
            ],
            'Doctor or Dentist Appointments' => [
                'title' => 'Dental Cleaning Appointment',
                'description' => 'Professional cleaning and oral assessment with a licensed dentist.',
                'location' => 'Greenbelt, Makati',
                'capacity' => 1,
                'price' => 1800,
                'discount_percentage' => 10,
                'queries' => ['dental clinic', 'dentist'],
            ],
            'Haircuts / Barbershop Visits' => [
                'title' => 'Premium Barber Cut',
                'description' => 'Includes wash, precision cut, and styling.',
                'location' => 'Kapitolyo, Pasig',
                'capacity' => 1,
                'price' => 650,
                'discount_percentage' => 10,
                'queries' => ['barbershop', 'haircut'],
            ],
            'Spa or Massage Sessions' => [
                'title' => '90-Minute Signature Massage',
                'description' => 'Full-body deep tissue massage with aromatherapy.',
                'location' => 'Alabang Town Center',
                'capacity' => 1,
                'price' => 2500,
                'discount_percentage' => 10,
                'queries' => ['spa', 'massage'],
            ],
            'Sports Courts (Tennis, Badminton, Basketball)' => [
                'title' => 'Indoor Sports Court Rental',
                'description' => 'Book a private indoor court with lighting included.',
                'location' => 'Marikina Sports Center',
                'capacity' => 12,
                'price' => 1800,
                'discount_percentage' => 10,
                'queries' => ['sports court', 'indoor court'],
            ],
            'Meeting or Conference Rooms' => [
                'title' => 'Boardroom for 12',
                'description' => 'Fully equipped room with projector, Wi-Fi, and refreshments.',
                'location' => 'Bonifacio High Street, BGC',
                'capacity' => 12,
                'price' => 6000,
                'discount_percentage' => 10,
                'queries' => ['meeting room', 'conference room'],
            ],
            'Photography Studios' => [
                'title' => 'Daylight Photo Studio',
                'description' => 'Natural light studio with seamless backdrops and basic lighting.',
                'location' => 'Quezon City',
                'capacity' => 6,
                'price' => 3200,
                'discount_percentage' => 10,
                'queries' => ['photo studio', 'photography'],
            ],
        ];

        Category::query()
            ->orderBy('id')
            ->get()
            ->each(function (Category $category, int $index) use ($admin, $now, $templates): void {
                $template = $templates[$category->name] ?? [
                    'title' => "{$category->name} Booking",
                    'description' => "Sample booking item for {$category->name}.",
                    'location' => "Metro Manila Hub " . ($index + 1),
                    'capacity' => 10 + ($index * 5),
                    'price' => 500 + ($index * 150),
                    'discount_percentage' => 10,
                    'queries' => [Str::slug($category->name)],
                ];

                $booking = Booking::firstOrCreate(
                    [
                        'title' => $template['title'],
                        'category_id' => $category->id,
                    ],
                    [
                        'description' => $template['description'],
                        'location' => $template['location'],
                        'event_date' => $now->copy()->addDays(3 + ($index * 2)),
                        'capacity' => $template['capacity'],
                        'price' => $template['price'],
                        'discount_percentage' => $template['discount_percentage'] ?? 0,
                        'created_by' => $admin->id,
                    ]
                );

                if ($booking->getMedia('images')->isEmpty()) {
                    $queries = $template['queries'] ?? [Str::slug($category->name)];
                    foreach ($queries as $imageIndex => $query) {
                        $slug = Str::slug($query) ?: 'booking-item';
                        $seed = "{$slug}-{$booking->id}-{$imageIndex}";
                        $booking
                            ->addMediaFromUrl("https://picsum.photos/seed/{$seed}/1200/800")
                            ->toMediaCollection('images');
                    }
                }
            });
    }
}
