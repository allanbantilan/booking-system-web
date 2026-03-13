<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('PHP');
            $table->string('checkout_id')->nullable();
            $table->string('checkout_url')->nullable();
            $table->string('reference')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('raw_webhook')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index(['checkout_id']);
            $table->index(['reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
