<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->index('user_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index('checkout_id');
            $table->index('reservation_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->index('category_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['checkout_id']);
            $table->dropIndex(['reservation_id']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['created_by']);
        });
    }
};
