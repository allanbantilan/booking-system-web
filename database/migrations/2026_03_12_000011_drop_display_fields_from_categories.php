<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $columns = [
                'quantity_label',
                'availability_label',
                'meta_line',
                'amenities',
            ];

            $existing = array_filter($columns, fn ($column) => Schema::hasColumn('categories', $column));

            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('quantity_label', 30)->default('slot(s)')->after('badge_label');
            $table->string('availability_label', 30)->default('Slots left')->after('quantity_label');
            $table->string('meta_line', 255)->nullable()->after('availability_label');
            $table->json('amenities')->nullable()->after('meta_line');
        });
    }
};
