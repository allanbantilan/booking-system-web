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
                'color' => 'cyan',
                'badge_label' => 'Flight',
            ],
            'hotels-accommodations' => [
                'color' => 'cyan',
                'badge_label' => 'Hotel',
            ],
            'car-rentals' => [
                'color' => 'cyan',
                'badge_label' => 'Car Rental',
            ],
            'restaurant-tables' => [
                'color' => 'amber',
                'badge_label' => 'Restaurant',
            ],
            'private-dining-rooms' => [
                'color' => 'amber',
                'badge_label' => 'Private Dining',
            ],
            'catering-services' => [
                'color' => 'amber',
                'badge_label' => 'Catering',
            ],
            'concert-tickets' => [
                'color' => 'pink',
                'badge_label' => 'Concert',
            ],
            'movies-cinemas' => [
                'color' => 'pink',
                'badge_label' => 'Cinema',
            ],
            'sports-games' => [
                'color' => 'purple',
                'badge_label' => 'Sports',
            ],
            'doctor-or-dentist-appointments' => [
                'color' => 'green',
                'badge_label' => 'Clinic',
            ],
            'haircuts-barbershop-visits' => [
                'color' => 'green',
                'badge_label' => 'Barbershop',
            ],
            'spa-or-massage-sessions' => [
                'color' => 'green',
                'badge_label' => 'Spa',
            ],
            'sports-courts-tennis-badminton-basketball' => [
                'color' => 'blue',
                'badge_label' => 'Court',
            ],
            'meeting-or-conference-rooms' => [
                'color' => 'blue',
                'badge_label' => 'Meeting Room',
            ],
            'photography-studios' => [
                'color' => 'blue',
                'badge_label' => 'Studio',
            ],
        ];

        foreach ($configs as $slug => $config) {
            $category = Category::query()->where('slug', $slug)->first();

            if (! $category) {
                continue;
            }

            $category->update([
                'color' => $config['color'],
                'badge_label' => $config['badge_label'],
            ]);
        }
    }
}
