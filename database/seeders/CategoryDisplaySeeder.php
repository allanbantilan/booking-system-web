<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryDisplaySeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            'flights' => [
                'icon' => null,
                'color' => 'cyan',
                'badge_label' => 'Flight',
                'quantity_label' => 'seat(s)',
                'availability_label' => 'Seats left',
                'meta_line' => 'Departs daily - Carry-on included',
                'amenities' => ['carry-on', 'meals', 'flexible-rebooking'],
            ],
            'hotels-accommodations' => [
                'icon' => null,
                'color' => 'cyan',
                'badge_label' => 'Hotel',
                'quantity_label' => 'night(s)',
                'availability_label' => 'Rooms left',
                'meta_line' => 'Check-in after 2 PM - Breakfast included',
                'amenities' => ['wifi', 'breakfast', 'parking'],
            ],
            'car-rentals' => [
                'icon' => null,
                'color' => 'cyan',
                'badge_label' => 'Car Rental',
                'quantity_label' => 'day(s)',
                'availability_label' => 'Cars left',
                'meta_line' => 'Pickup anytime - Unlimited mileage',
                'amenities' => ['automatic', 'insurance', 'unlimited-mileage'],
            ],
            'restaurant-tables' => [
                'icon' => null,
                'color' => 'amber',
                'badge_label' => 'Restaurant',
                'quantity_label' => 'person(s)',
                'availability_label' => 'Tables left',
                'meta_line' => 'Chefs specials - Indoor or outdoor',
                'amenities' => ['chef-selection', 'indoor', 'outdoor'],
            ],
            'private-dining-rooms' => [
                'icon' => null,
                'color' => 'amber',
                'badge_label' => 'Private Dining',
                'quantity_label' => 'person(s)',
                'availability_label' => 'Rooms left',
                'meta_line' => 'Private room - Dedicated host',
                'amenities' => ['private-room', 'custom-menu', 'dedicated-host'],
            ],
            'catering-services' => [
                'icon' => null,
                'color' => 'amber',
                'badge_label' => 'Catering',
                'quantity_label' => 'package(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Buffet setup - Staff included',
                'amenities' => ['buffet', 'setup', 'staff'],
            ],
            'concert-tickets' => [
                'icon' => null,
                'color' => 'pink',
                'badge_label' => 'Concert',
                'quantity_label' => 'ticket(s)',
                'availability_label' => 'Tickets left',
                'meta_line' => 'VIP entry - Great view',
                'amenities' => ['vip-entry', 'merch-bundle', 'great-view'],
            ],
            'movies-cinemas' => [
                'icon' => null,
                'color' => 'pink',
                'badge_label' => 'Cinema',
                'quantity_label' => 'ticket(s)',
                'availability_label' => 'Tickets left',
                'meta_line' => 'Recliner seats - Snacks included',
                'amenities' => ['recliner', 'snacks', 'dolby-sound'],
            ],
            'sports-games' => [
                'icon' => null,
                'color' => 'purple',
                'badge_label' => 'Sports',
                'quantity_label' => 'ticket(s)',
                'availability_label' => 'Tickets left',
                'meta_line' => 'Courtside vibe - Fan zone access',
                'amenities' => ['courtside', 'vip-entry', 'fan-zone'],
            ],
            'doctor-or-dentist-appointments' => [
                'icon' => null,
                'color' => 'green',
                'badge_label' => 'Clinic',
                'quantity_label' => 'slot(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Consultation - Priority slots',
                'amenities' => ['consultation', 'follow-up', 'priority-slot'],
            ],
            'haircuts-barbershop-visits' => [
                'icon' => null,
                'color' => 'green',
                'badge_label' => 'Barbershop',
                'quantity_label' => 'slot(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Wash and style - Beard trim',
                'amenities' => ['wash', 'styling', 'beard-trim'],
            ],
            'spa-or-massage-sessions' => [
                'icon' => null,
                'color' => 'green',
                'badge_label' => 'Spa',
                'quantity_label' => 'person(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Private room - Aromatherapy',
                'amenities' => ['private-room', 'aromatherapy', 'hot-stone'],
            ],
            'sports-courts-tennis-badminton-basketball' => [
                'icon' => null,
                'color' => 'blue',
                'badge_label' => 'Court',
                'quantity_label' => 'hour(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Equipment included - Lighting ready',
                'amenities' => ['equipment', 'lighting', 'showers'],
            ],
            'meeting-or-conference-rooms' => [
                'icon' => null,
                'color' => 'blue',
                'badge_label' => 'Meeting Room',
                'quantity_label' => 'hour(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Projector - Refreshments',
                'amenities' => ['projector', 'wifi', 'refreshments'],
            ],
            'photography-studios' => [
                'icon' => null,
                'color' => 'blue',
                'badge_label' => 'Studio',
                'quantity_label' => 'hour(s)',
                'availability_label' => 'Slots left',
                'meta_line' => 'Lighting setup - Backdrops ready',
                'amenities' => ['lighting', 'backdrops', 'assistant'],
            ],
        ];

        foreach ($configs as $slug => $config) {
            $category = Category::query()->where('slug', $slug)->first();

            if (! $category) {
                continue;
            }

            $category->update([
                'icon' => $config['icon'],
                'color' => $config['color'],
                'badge_label' => $config['badge_label'],
                'quantity_label' => $config['quantity_label'],
                'availability_label' => $config['availability_label'],
                'meta_line' => $config['meta_line'],
                'amenities' => $config['amenities'],
            ]);
        }
    }
}
