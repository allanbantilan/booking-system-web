<?php

namespace Database\Seeders;

use App\Models\BackendUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BackendUsersSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = BackendUser::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Hello123!'),
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->syncRoles(['super_admin']);
    }
}
