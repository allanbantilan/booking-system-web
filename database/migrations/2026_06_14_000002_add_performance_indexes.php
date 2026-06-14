<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // reservations.user_id — queried on every history page load, not yet indexed.
        Schema::table('reservations', function (Blueprint $table): void {
            $table->index('user_id');
        });

        // bookings.created_by — joined when loading creator info, not yet indexed.
        // (category_id and checkout_id already have indexes from earlier migrations.)
        Schema::table('bookings', function (Blueprint $table): void {
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['created_by']);
        });
    }
};
