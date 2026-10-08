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
                'mobile_number' => '09170000001',
                'facebook_url' => 'https://facebook.com/superadmin',
                'instagram_url' => 'https://instagram.com/superadmin',
            ]
        );

        $superAdmin->syncRoles(['super_admin', 'admin']);

        // Read-only demo account for recruiters/reviewers: can view the back
        // office but cannot create, update, or delete seeded data.
        $viewer = BackendUser::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Demo Viewer',
                'password' => Hash::make('Hello123!'),
                'email_verified_at' => now(),
                'mobile_number' => '09170000002',
                'facebook_url' => 'https://facebook.com/demoviewer',
                'instagram_url' => 'https://instagram.com/demoviewer',
            ]
        );

        $viewer->syncRoles(['support_staff']);
    }
}
