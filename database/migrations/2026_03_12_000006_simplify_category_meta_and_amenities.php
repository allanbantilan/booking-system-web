<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            if (Schema::hasColumn('categories', 'meta_line_template')) {
                $table->dropColumn('meta_line_template');
            }

            $table->string('meta_line', 255)->nullable()->after('availability_label');
            $table->json('amenities')->nullable()->after('meta_line');
        });

        Schema::dropIfExists('category_amenities');
    }

    public function down(): void
    {
        Schema::create('category_amenities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('amenity_key', 50);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table): void {
            if (Schema::hasColumn('categories', 'meta_line')) {
                $table->dropColumn('meta_line');
            }

            if (Schema::hasColumn('categories', 'amenities')) {
                $table->dropColumn('amenities');
            }

            $table->string('meta_line_template', 255)->nullable()->after('availability_label');
        });
    }
};
