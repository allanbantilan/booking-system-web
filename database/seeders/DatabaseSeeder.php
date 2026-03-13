<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CategoriesSeeder::class,
            CategoryDisplaySeeder::class,
            FrontendUsersSeeder::class,
            BookingItemsSeeder::class,
            ReservationsSeeder::class,
            BackendUsersSeeder::class,
            BackendUserContactSeeder::class,
        ]);
    }
}
