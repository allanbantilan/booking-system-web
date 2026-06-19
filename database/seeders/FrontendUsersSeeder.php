<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FrontendUsersSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = User::updateOrCreate(
                ['email' => "testuser{$i}@example.com"],
                [
                    'name' => "Test User {$i}",
                    'password' => Hash::make('Hello123!'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['customer']);
        }

        // Real owner account — receives transactional email (Resend sandbox only
        // delivers to this address until a sending domain is verified).
        $owner = User::updateOrCreate(
            ['email' => 'allanbantilan11@gmail.com'],
            [
                'name' => 'Allan Bantilan',
                'password' => Hash::make('Hello123!'),
                'email_verified_at' => now(),
            ]
        );

        $owner->syncRoles(['customer']);
    }
}
