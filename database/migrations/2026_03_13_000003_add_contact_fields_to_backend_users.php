<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backend_users', function (Blueprint $table): void {
            $table->string('mobile_number', 30)->nullable()->after('email');
            $table->string('facebook_url')->nullable()->after('mobile_number');
            $table->string('instagram_url')->nullable()->after('facebook_url');
        });
    }

    public function down(): void
    {
        Schema::table('backend_users', function (Blueprint $table): void {
            $table->dropColumn(['mobile_number', 'facebook_url', 'instagram_url']);
        });
    }
};
