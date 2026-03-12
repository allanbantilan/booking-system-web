<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('icon', 10)->nullable()->after('slug');
            $table->string('color', 20)->default('slate')->after('icon');
            $table->string('badge_label', 50)->nullable()->after('color');
            $table->string('quantity_label', 30)->default('slot(s)')->after('badge_label');
            $table->string('availability_label', 30)->default('Slots left')->after('quantity_label');
            $table->string('meta_line_template', 255)->nullable()->after('availability_label');
        });

        Schema::create('category_amenities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('amenity_key', 50);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_amenities');

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn([
                'icon',
                'color',
                'badge_label',
                'quantity_label',
                'availability_label',
                'meta_line_template',
            ]);
        });
    }
};
