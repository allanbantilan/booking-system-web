<?php

use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $backendUsers = BackendUser::query()->orderBy('id')->get();

        if ($backendUsers->isEmpty()) {
            return;
        }

        $now = now();
        $shouldSeedImages = filter_var(env('SEED_EXTERNAL_IMAGES', false), FILTER_VALIDATE_BOOLEAN);

        $templates = [
            'Flights' => [
                'title' => 'Manila to Cebu Roundtrip Seat',
                'description' => 'Secure an economy seat with free carry-on and flexible rebooking. Ideal for weekend getaways and business trips.',
                'location' => 'NAIA Terminal 3, Pasay',
                'capacity' => 180,
                'price' => 3499,
                'discount_percentage' => 10,
                'quantity_label' => 'seat(s)',
                'availability_label' => 'Seats left',
                'meta_line' => 'Departs daily - Carry-on included',
                'amenities' => ['carry-on', 'meals', 'flexible-rebooking'],
                'queries' => ['airplane', 'airport'],
            ],
            'Hotels / Accommodations' => [
                'title' => 'Boracay Beachfront King Room',
                'description' => 'One-night stay with breakfast and ocean view. Check-in after 2 PM, late checkout available.',
                'location' => 'White Beach, Boracay',
                'capacity' => 2,
                'price' => 6800,
                'discount_percentage' => 10,
                'quantity_label' => 'night(s)',
                'availability_label' => 'Rooms left',
                'meta_line' => 'Check-in after 2 PM - Breakfast included',
                'amenities' => ['wifi', 'breakfast', 'parking'],
                'queries' => ['hotel room', 'resort'],
            ],
            'Car Rentals' => [
                'title' => 'Compact Car Rental (24 Hours)',
                'description' => 'Fuel-efficient compact car with automatic transmission. Includes basic insurance and unlimited mileage.',
                'location' => 'Makati CBD',
                'capacity' => 5,
                'price' => 2200,
                'discount_percentage' => 10,
                'quantity_label' => 'day(s)',
                'availability_label' => 'Cars left',
                'meta_line' => 'Pickup anytime - Unlimited mileage',
                'amenities' => ['automatic', 'insurance', 'unlimited-mileage'],
                'queries' => ['car rental', 'compact car'],
            ],
            'Restaurant Tables' => [
                'title' => 'Chef\'s Tasting Table for Two',
                'description' => 'Reserve a prime dinner slot with a curated six-course tasting menu.',
                'location' => 'Poblacion, Makati',
                'capacity' => 2,
                'price' => 3200,
                'discount_percentage' => 10,
                'quantity_label' => 'person(s)',
                'availability_label' => 'Tables left',
                'meta_line' => 'Chefs specials - Indoor or outdoor',
                'amenities' => ['chef-selection', 'indoor', 'outdoor'],
                'queries' => ['restaurant', 'fine dining'],
            ],
            'Private Dining Rooms' => [
                'title' => 'Private Dining Room (10 Guests)',
                'description' => 'Exclusive room with dedicated server and customizable menu packages.',
                'location' => 'BGC, Taguig',
                'capacity' => 10,
                'price' => 9500,
                'discount_percentage' => 10,
                'quantity_label' => 'person(s)',
                'availability_label' => 'Rooms left',
                'meta_line' => 'Private room - Dedicated host',
                'amenities' => ['private-room', 'custom-menu', 'dedicated-host'],
                'queries' => ['private dining', 'banquet room'],
            ],
            'Catering Services' => [
                'title' => 'Corporate Catering Package',
                'description' => 'Buffet setup with appetizers, mains, and desserts for small corporate events.',
                'location' => 'Ortigas Center, Pasig',
                'capacity' => 30,
                'price' => 18000,
                'discount_percentage' => 10,
                'quantity_label' => 'package(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Buffet setup - Staff included',
                'amenities' => ['buffet', 'setup', 'staff'],
                'queries' => ['catering', 'buffet'],
            ],
            'Concert Tickets' => [
                'title' => 'Arena Concert Lower Box',
                'description' => 'Lower box seat for a live concert with clear stage visibility.',
                'location' => 'Smart Araneta Coliseum, QC',
                'capacity' => 1,
                'price' => 4800,
                'discount_percentage' => 10,
                'quantity_label' => 'ticket(s)',
                'availability_label' => 'Tickets left',
                'meta_line' => 'VIP entry - Great view',
                'amenities' => ['vip-entry', 'merch-bundle', 'great-view'],
                'queries' => ['concert', 'arena'],
            ],
            'Movies (Cinemas)' => [
                'title' => 'Cinema Luxe Recliner Seats',
                'description' => 'Reserve two recliner seats with in-seat dining service.',
                'location' => 'Uptown Mall, BGC',
                'capacity' => 2,
                'price' => 1400,
                'discount_percentage' => 10,
                'quantity_label' => 'ticket(s)',
                'availability_label' => 'Tickets left',
                'meta_line' => 'Recliner seats - Snacks included',
                'amenities' => ['recliner', 'snacks', 'dolby-sound'],
                'queries' => ['cinema', 'movie theater'],
            ],
            'Sports Games' => [
                'title' => 'Courtside Sports Pass',
                'description' => 'Courtside ticket for a pro league game with VIP entry.',
                'location' => 'MOA Arena, Pasay',
                'capacity' => 1,
                'price' => 5200,
                'discount_percentage' => 10,
                'quantity_label' => 'ticket(s)',
                'availability_label' => 'Tickets left',
                'meta_line' => 'Courtside vibe - Fan zone access',
                'amenities' => ['courtside', 'vip-entry', 'fan-zone'],
                'queries' => ['stadium', 'basketball game'],
            ],
            'Doctor or Dentist Appointments' => [
                'title' => 'Dental Cleaning Appointment',
                'description' => 'Professional cleaning and oral assessment with a licensed dentist.',
                'location' => 'Greenbelt, Makati',
                'capacity' => 1,
                'price' => 1800,
                'discount_percentage' => 10,
                'quantity_label' => 'slot(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Consultation - Priority slots',
                'amenities' => ['consultation', 'follow-up', 'priority-slot'],
                'queries' => ['dental clinic', 'dentist'],
            ],
            'Haircuts / Barbershop Visits' => [
                'title' => 'Premium Barber Cut',
                'description' => 'Includes wash, precision cut, and styling.',
                'location' => 'Kapitolyo, Pasig',
                'capacity' => 1,
                'price' => 650,
                'discount_percentage' => 10,
                'quantity_label' => 'slot(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Wash and style - Beard trim',
                'amenities' => ['wash', 'styling', 'beard-trim'],
                'queries' => ['barbershop', 'haircut'],
            ],
            'Spa or Massage Sessions' => [
                'title' => '90-Minute Signature Massage',
                'description' => 'Full-body deep tissue massage with aromatherapy.',
                'location' => 'Alabang Town Center',
                'capacity' => 1,
                'price' => 2500,
                'discount_percentage' => 10,
                'quantity_label' => 'person(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Private room - Aromatherapy',
                'amenities' => ['private-room', 'aromatherapy', 'hot-stone'],
                'queries' => ['spa', 'massage'],
            ],
            'Sports Courts (Tennis, Badminton, Basketball)' => [
                'title' => 'Indoor Sports Court Rental',
                'description' => 'Book a private indoor court with lighting included.',
                'location' => 'Marikina Sports Center',
                'capacity' => 12,
                'price' => 1800,
                'discount_percentage' => 10,
                'quantity_label' => 'hour(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Equipment included - Lighting ready',
                'amenities' => ['equipment', 'lighting', 'showers'],
                'queries' => ['sports court', 'indoor court'],
            ],
            'Meeting or Conference Rooms' => [
                'title' => 'Boardroom for 12',
                'description' => 'Fully equipped room with projector, Wi-Fi, and refreshments.',
                'location' => 'Bonifacio High Street, BGC',
                'capacity' => 12,
                'price' => 6000,
                'discount_percentage' => 10,
                'quantity_label' => 'hour(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Projector - Refreshments',
                'amenities' => ['projector', 'wifi', 'refreshments'],
                'queries' => ['meeting room', 'conference room'],
            ],
            'Photography Studios' => [
                'title' => 'Daylight Photo Studio',
                'description' => 'Natural light studio with seamless backdrops and basic lighting.',
                'location' => 'Quezon City',
                'capacity' => 6,
                'price' => 3200,
                'discount_percentage' => 10,
                'quantity_label' => 'hour(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Lighting setup - Backdrops ready',
                'amenities' => ['lighting', 'backdrops', 'assistant'],
                'queries' => ['photo studio', 'photography'],
            ],
        ];

        Category::query()
            ->orderBy('id')
            ->get()
            ->each(function (Category $category, int $index) use ($backendUsers, $now, $templates, $shouldSeedImages): void {
                $template = $templates[$category->name] ?? [
                    'title' => "{$category->name} Booking",
                    'description' => "Sample booking item for {$category->name}.",
                    'location' => 'Metro Manila Hub ' . ($index + 1),
                    'capacity' => 10 + ($index * 5),
                    'price' => 500 + ($index * 150),
                    'discount_percentage' => 10,
                    'quantity_label' => 'slot(s)',
                    'availability_label' => 'Slots left',
                    'meta_line' => 'Available booking slot',
                    'amenities' => ['availability', 'support', 'secure'],
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
                        'availability_label' => $template['availability_label'],
                        'quantity_label' => $template['quantity_label'],
                        'meta_line' => $template['meta_line'],
                        'amenities' => $template['amenities'],
                        'price' => $template['price'],
                        'discount_percentage' => $template['discount_percentage'] ?? 0,
                        'created_by' => $backendUsers[$index % $backendUsers->count()]->id,
                    ]
                );

                if ($shouldSeedImages && $booking->getMedia('images')->isEmpty()) {
                    $queries = $template['queries'] ?? [Str::slug($category->name)];
                    $query = $queries[0] ?? 'booking';
                    $downloaded = 0;

                    for ($imageIndex = 1; $imageIndex <= 3; $imageIndex++) {
                        $seed = Str::slug($query) ?: 'booking-item';
                        $url = "https://picsum.photos/seed/{$seed}-{$imageIndex}/1200/800";

                        try {
                            $booking
                                ->addMediaFromUrl($url)
                                ->toMediaCollection('images');
                            $downloaded++;
                        } catch (\Throwable $exception) {
                            continue;
                        }
                    }

                    if ($downloaded > 0) {
                        return;
                    }
                }

                if ($booking->getMedia('images')->isEmpty()) {
                    $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="100%" stop-color="#1f2937"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/>
  <rect x="80" y="90" width="1040" height="620" rx="28" fill="#111827" stroke="#334155" stroke-width="4"/>
  <text x="120" y="210" fill="#e2e8f0" font-size="48" font-family="Arial, sans-serif" font-weight="700">Sample Booking</text>
  <text x="120" y="280" fill="#94a3b8" font-size="28" font-family="Arial, sans-serif">Placeholder Image</text>
</svg>
SVG;

                    $booking
                        ->addMediaFromString($svg)
                        ->usingFileName('booking-placeholder.svg')
                        ->toMediaCollection('images');
                }
            });
    }

    public function down(): void
    {
        Booking::query()->delete();
    }
};
