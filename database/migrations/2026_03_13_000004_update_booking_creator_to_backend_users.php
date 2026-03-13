<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'created_by')) {
            $this->dropForeignIfExists('bookings', 'created_by');
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });

        $fallbackId = DB::table('backend_users')->min('id');
        if ($fallbackId) {
            DB::table('bookings')
                ->whereNotIn('created_by', function ($query) {
                    $query->select('id')->from('backend_users');
                })
                ->update(['created_by' => $fallbackId]);
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('created_by')->references('id')->on('backend_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'created_by')) {
            $this->dropForeignIfExists('bookings', 'created_by');
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('CONSTRAINT_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
