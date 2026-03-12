<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Flights',
            'Hotels / Accommodations',
            'Car Rentals',
            'Restaurant Tables',
            'Private Dining Rooms',
            'Catering Services',
            'Concert Tickets',
            'Movies (Cinemas)',
            'Sports Games',
            'Doctor or Dentist Appointments',
            'Haircuts / Barbershop Visits',
            'Spa or Massage Sessions',
            'Sports Courts (Tennis, Badminton, Basketball)',
            'Meeting or Conference Rooms',
            'Photography Studios',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)]
            );
        }
    }
}
