<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_cancellation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained('backend_users')->nullOnDelete();
            $table->string('status')->default('requested');
            $table->text('reason')->nullable();
            $table->text('merchant_note')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('refund_required')->default(false);
            $table->string('refund_status')->default('not_required');
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['merchant_id', 'status']);
            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_cancellation_requests');
    }
};
