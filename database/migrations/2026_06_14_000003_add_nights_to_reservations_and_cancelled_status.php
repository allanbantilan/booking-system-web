<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add nights column if it doesn't already exist (it was missing from migrations).
        if (!Schema::hasColumn('reservations', 'nights')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->unsignedInteger('nights')->default(1)->after('quantity');
            });
        }

        // Expand the status ENUM to include 'cancelled' (MySQL only — SQLite ignores ENUMs).
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending', 'confirmed') DEFAULT 'pending'");
        }

        if (Schema::hasColumn('reservations', 'nights')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->dropColumn('nights');
            });
        }
    }
};
