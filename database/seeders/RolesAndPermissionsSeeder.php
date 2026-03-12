<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $webPermissions = [
            'book bookings',
            'view own reservations',
            'cancel own reservations',
        ];

        foreach ($webPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $customerRole = Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'web',
        ]);
        $customerRole->syncPermissions($webPermissions);

        $backendPermissions = [
            'dashboard.view',
            'bookings.view',
            'bookings.create',
            'bookings.update',
            'bookings.delete',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'backend_users.view',
            'backend_users.create',
            'backend_users.update',
            'backend_users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'media.view',
            'media.upload',
            'media.delete',
            'settings.view',
            'settings.update',
        ];

        foreach ($backendPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'backend',
            ]);
        }

        $cmsRoles = [
            'super_admin' => $backendPermissions,
            'content_manager' => [
                'dashboard.view',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.delete',
                'categories.view',
                'categories.create',
                'categories.update',
                'categories.delete',
                'media.view',
                'media.upload',
                'media.delete',
            ],
            'event_manager' => [
                'dashboard.view',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.delete',
                'categories.view',
                'media.view',
                'media.upload',
            ],
            'user_manager' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'backend_users.view',
                'backend_users.create',
                'backend_users.update',
                'backend_users.delete',
                'roles.view',
            ],
            'support_staff' => [
                'dashboard.view',
                'bookings.view',
                'users.view',
                'media.view',
            ],
        ];

        foreach ($cmsRoles as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'backend',
            ]);

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
