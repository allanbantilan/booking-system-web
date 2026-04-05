<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('booking_type')->default('event')->after('title');
        });

        DB::table('bookings')
            ->whereNull('booking_type')
            ->update(['booking_type' => 'event']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('booking_type');
        });
    }
};
