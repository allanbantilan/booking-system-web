<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('status')->default('pending');
            $table->foreignId('backend_user_id')->nullable()->constrained('backend_users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('backend_users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_requests');
    }
};

