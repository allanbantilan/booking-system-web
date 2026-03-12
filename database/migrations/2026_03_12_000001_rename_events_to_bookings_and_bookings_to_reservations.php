<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropForeign(['event_id']);
            $table->dropIndex(['event_id', 'status']);
        });

        Schema::rename('bookings', 'reservations');
        Schema::rename('events', 'bookings');

        Schema::table('reservations', function (Blueprint $table): void {
            $table->renameColumn('event_id', 'booking_id');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropForeign(['booking_id']);
            $table->dropIndex(['booking_id', 'status']);
            $table->renameColumn('booking_id', 'event_id');
        });

        Schema::rename('bookings', 'events');
        Schema::rename('reservations', 'bookings');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->index(['event_id', 'status']);
        });
    }
};
