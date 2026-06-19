<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BackendUser;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingItemsSeeder extends Seeder
{
    private const UNSPLASH_IMAGE_IDS = [
        'airplane' => ['photo-1436491865332-7a61a109cc05', 'photo-1556388158-158ea5ccacbd', 'photo-1569154941061-e231b4725ef1'],
        'hotel-room' => ['photo-1566073771259-6a8506099945', 'photo-1578683010236-d716f9a3f461', 'photo-1590490360182-c33d57733427'],
        'car-rental' => ['photo-1503376780353-7e6692767b70', 'photo-1549924231-f129b911e442', 'photo-1492144534655-ae79c964c9d7'],
        'restaurant' => ['photo-1414235077428-338989a2e8c0', 'photo-1555396273-367ea4eb4db5', 'photo-1517248135467-4c7edcad34c4'],
        'private-dining' => ['photo-1551218808-94e220e084d2', 'photo-1559339352-11d035aa65de', 'photo-1528605248644-14dd04022da1'],
        'catering' => ['photo-1555244162-803834f70033', 'photo-1543353071-10c8ba85a904', 'photo-1527529482837-4698179dc6ce'],
        'concert' => ['photo-1501386761578-eac5c94b800a', 'photo-1493225457124-a3eb161ffa5f', 'photo-1516450360452-9312f5e86fc7'],
        'cinema' => ['photo-1489599849927-2ee91cede3ba', 'photo-1440404653325-ab127d49abc1', 'photo-1524985069026-dd778a71c7b4'],
        'stadium' => ['photo-1546519638-68e109498ffc', 'photo-1461896836934-ffe607ba8211', 'photo-1519861531473-9200262188bf'],
        'dental-clinic' => ['photo-1629909613654-28e377c37b09', 'photo-1606811971618-4486d14f3f99', 'photo-1588776814546-1ffcf47267a5'],
        'barbershop' => ['photo-1585747860715-2ba37e788b70', 'photo-1512690459411-b9245aed614b', 'photo-1621605815971-fbc98d665033'],
        'spa' => ['photo-1540555700478-4be289fbecef', 'photo-1519823551278-64ac92734fb1', 'photo-1600334089648-b0d9d3028eb2'],
        'sports-court' => ['photo-1546519638-68e109498ffc', 'photo-1526232761682-d26e03ac148e', 'photo-1626224583764-f87db24ac4ea'],
        'meeting-room' => ['photo-1497366754035-f200968a6e72', 'photo-1517502884422-41eaead166d4', 'photo-1564069114553-7215e1ff1890'],
        'photo-studio' => ['photo-1516035069371-29a1b244cc32', 'photo-1502982720700-bfff97f2ecac', 'photo-1492691527719-9d1e07e534b4'],
        'booking' => ['photo-1497366811353-6870744d04b2', 'photo-1500530855697-b586d89ba3ee', 'photo-1522708323590-d24dbb6b0267'],
    ];

    public function run(): void
    {
        $merchants = BackendUser::query()
            ->whereHas('roles', fn ($query) => $query
                ->where('name', 'merchant')
                ->where('guard_name', 'backend'))
            ->orderBy('id')
            ->get();

        if ($merchants->isEmpty()) {
            return;
        }

        $now = now();
        $shouldSeedImages = filter_var(env('SEED_EXTERNAL_IMAGES', false), FILTER_VALIDATE_BOOLEAN);

        $templates = [
            'Flights' => [
                'title' => 'Roundtrip Flight',
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
                'title' => 'Hotel Room',
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
                'title' => 'Compact Car Rental',
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
                'title' => 'Dinner for Two',
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
                'title' => 'Private Dining Room',
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
                'title' => 'Catering Package',
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
                'title' => 'Concert Ticket',
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
                'title' => 'Cinema Seats',
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
                'title' => 'Sports Game Ticket',
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
                'title' => 'Dental Checkup',
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
                'title' => 'Haircut Appointment',
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
                'title' => 'Massage Session',
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
                'title' => 'Sports Court Rental',
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
                'title' => 'Meeting Room',
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
                'title' => 'Photo Studio',
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
            ->each(function (Category $category, int $index) use ($merchants, $now, $templates, $shouldSeedImages): void {
                $template = $templates[$category->name] ?? [
                    'title' => "{$category->name} Booking",
                    'description' => "Sample booking item for {$category->name}.",
                    'location' => "Metro Manila Hub " . ($index + 1),
                    'capacity' => 10 + ($index * 5),
                    'price' => 500 + ($index * 150),
                    'discount_percentage' => 10,
                    'quantity_label' => 'slot(s)',
                    'availability_label' => 'Slots left',
                    'meta_line' => 'Available booking slot',
                    'amenities' => ['availability', 'support', 'secure'],
                    'queries' => [Str::slug($category->name)],
                ];

                $merchant = $merchants[$index % $merchants->count()];

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
                        'created_by' => $merchant->id,
                    ]
                );

                if ((int) $booking->created_by !== $merchant->id) {
                    $booking->update(['created_by' => $merchant->id]);
                }

                if ($booking->getMedia('images')->isEmpty()) {
                    $downloaded = 0;

                    if ($shouldSeedImages) {
                        foreach ($this->unsplashImageUrls($template) as $url) {
                            try {
                                $booking
                                    ->addMediaFromUrl($url)
                                    ->toMediaCollection('images');
                                $downloaded++;
                            } catch (\Throwable $exception) {
                                continue;
                            }
                        }
                    }

                    // Offline / failed download: a placeholder that still names the item.
                    if ($downloaded === 0) {
                        $booking
                            ->addMediaFromString($this->placeholderSvg($booking->title, $category->name, (float) $booking->price))
                            ->usingFileName('booking-placeholder.svg')
                            ->toMediaCollection('images');
                    }
                }
            });
    }

    /** @return array<int, string> */
    private function unsplashImageUrls(array $template): array
    {
        $key = collect($template['queries'] ?? [])
            ->map(fn ($query) => Str::slug($query))
            ->first(fn ($query) => isset(self::UNSPLASH_IMAGE_IDS[$query])) ?? 'booking';

        return collect(self::UNSPLASH_IMAGE_IDS[$key])
            ->map(fn (string $id): string => "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=1200&h=800&q=80")
            ->all();
    }

    private function placeholderSvg(string $title, string $category, float $price): string
    {
        $flags = ENT_QUOTES | ENT_XML1;
        $title = htmlspecialchars(Str::limit($title, 40), $flags, 'UTF-8');
        $category = htmlspecialchars(Str::upper(Str::limit($category, 36)), $flags, 'UTF-8');
        $priceLabel = htmlspecialchars('PHP ' . number_format($price, 2), $flags, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="100%" stop-color="#1f2937"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/>
  <rect x="80" y="90" width="1040" height="620" rx="28" fill="#111827" stroke="#334155" stroke-width="4"/>
  <text x="120" y="210" fill="#38bdf8" font-size="26" font-family="Arial, sans-serif" font-weight="600" letter-spacing="2">{$category}</text>
  <text x="120" y="310" fill="#e2e8f0" font-size="56" font-family="Arial, sans-serif" font-weight="700">{$title}</text>
  <text x="120" y="390" fill="#94a3b8" font-size="34" font-family="Arial, sans-serif">{$priceLabel}</text>
</svg>
SVG;
    }
}
