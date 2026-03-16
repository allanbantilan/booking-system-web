<?php

use App\Models\BackendUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $backendPermissions = [
            'view dashboard',
            'view any booking',
            'view booking',
            'create booking',
            'update booking',
            'delete booking',
            'view any reservation',
            'view reservation',
            'create reservation',
            'update reservation',
            'delete reservation',
            'view any payment',
            'view payment',
            'create payment',
            'update payment',
            'delete payment',
            'view any receipt',
            'view receipt',
            'create receipt',
            'update receipt',
            'delete receipt',
            'view any category',
            'view category',
            'create category',
            'update category',
            'delete category',
            'view any user',
            'view user',
            'create user',
            'update user',
            'delete user',
            'view any backend user',
            'view backend user',
            'create backend user',
            'update backend user',
            'delete backend user',
            'view any role',
            'view role',
            'create role',
            'update role',
            'delete role',
            'view any media',
            'view media',
            'upload media',
            'delete media',
            'view any setting',
            'view setting',
            'update setting',
        ];

        foreach ($backendPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'backend',
            ]);
        }

        $roles = [
            'admin' => $backendPermissions,
            'super_admin' => $backendPermissions,
            'merchant' => [
                'view any booking',
                'view booking',
                'create booking',
                'update booking',
                'delete booking',
            ],
            'content_manager' => [
                'view dashboard',
                'view any booking',
                'view booking',
                'create booking',
                'update booking',
                'delete booking',
                'view any category',
                'view category',
                'create category',
                'update category',
                'delete category',
                'view any media',
                'view media',
                'upload media',
                'delete media',
            ],
            'event_manager' => [
                'view dashboard',
                'view any booking',
                'view booking',
                'create booking',
                'update booking',
                'delete booking',
                'view any category',
                'view category',
                'view any media',
                'view media',
                'upload media',
            ],
            'user_manager' => [
                'view dashboard',
                'view any user',
                'view user',
                'create user',
                'update user',
                'delete user',
                'view any backend user',
                'view backend user',
                'create backend user',
                'update backend user',
                'delete backend user',
                'view any role',
                'view role',
            ],
            'support_staff' => [
                'view dashboard',
                'view any booking',
                'view booking',
                'view any user',
                'view user',
                'view any media',
                'view media',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'backend',
            ]);

            $role->syncPermissions($permissions);
        }

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

        $sampleUsers = [
            'admin@example.com' => ['name' => 'Admin User', 'roles' => ['admin']],
            'merchant@example.com' => ['name' => 'Merchant User', 'roles' => ['merchant']],
            'content.manager@example.com' => ['name' => 'Content Manager', 'roles' => ['content_manager']],
            'event.manager@example.com' => ['name' => 'Event Manager', 'roles' => ['event_manager']],
            'user.manager@example.com' => ['name' => 'User Manager', 'roles' => ['user_manager']],
            'support.staff@example.com' => ['name' => 'Support Staff', 'roles' => ['support_staff']],
        ];

        foreach ($sampleUsers as $email => $data) {
            $user = BackendUser::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('Hello123!'),
                    'email_verified_at' => now(),
                    'mobile_number' => '09170000002',
                    'facebook_url' => 'https://facebook.com/sample',
                    'instagram_url' => 'https://instagram.com/sample',
                ]
            );

            $user->syncRoles($data['roles']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $backendRoleNames = [
            'admin',
            'super_admin',
            'merchant',
            'content_manager',
            'event_manager',
            'user_manager',
            'support_staff',
        ];

        Role::query()
            ->where('guard_name', 'backend')
            ->whereIn('name', $backendRoleNames)
            ->delete();

        Permission::query()
            ->where('guard_name', 'backend')
            ->delete();

        BackendUser::query()
            ->whereIn('email', [
                'superadmin@example.com',
                'admin@example.com',
                'merchant@example.com',
                'content.manager@example.com',
                'event.manager@example.com',
                'user.manager@example.com',
                'support.staff@example.com',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
