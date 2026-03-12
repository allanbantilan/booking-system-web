<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'quantity_label')) {
                $table->string('quantity_label', 30)
                    ->default('slot(s)')
                    ->after(Schema::hasColumn('bookings', 'availability_label') ? 'availability_label' : 'capacity');
            }

            if (! Schema::hasColumn('bookings', 'meta_line')) {
                $table->string('meta_line', 255)
                    ->nullable()
                    ->after('quantity_label');
            }

            if (! Schema::hasColumn('bookings', 'amenities')) {
                $table->json('amenities')
                    ->nullable()
                    ->after('meta_line');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['quantity_label', 'meta_line', 'amenities']);
        });
    }
};
