<?php

namespace Database\Seeders;

use App\Models\BackendUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BackendUserContactSeeder extends Seeder
{
    public function run(): void
    {
        BackendUser::query()->get()->each(function (BackendUser $user): void {
            $slug = Str::slug($user->name ?: 'backend-user');

            $user->mobile_number = $user->mobile_number ?: '0917' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT);
            $user->facebook_url = $user->facebook_url ?: "https://facebook.com/{$slug}";
            $user->instagram_url = $user->instagram_url ?: "https://instagram.com/{$slug}";
            $user->save();
        });
    }
}
