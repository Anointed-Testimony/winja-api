<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points_earned')->default(0); // Total points earned
            $table->integer('points_spent')->default(0); // Total points spent/withdrawn
            $table->integer('points_balance')->default(0); // Current points balance
            $table->decimal('total_earnings', 10, 2)->default(0.00); // Total earnings in Naira
            $table->decimal('withdrawn_amount', 10, 2)->default(0.00); // Total amount withdrawn
            $table->decimal('pending_withdrawal', 10, 2)->default(0.00); // Pending withdrawal amount
            $table->timestamps();

            // Ensure one record per user
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_points');
    }
}; 