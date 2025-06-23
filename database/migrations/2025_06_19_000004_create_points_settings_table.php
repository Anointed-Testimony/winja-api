<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('point_value_in_naira', 10, 2)->default(1.00); // How much 1 point = ₦X
            $table->decimal('minimum_withdrawal', 10, 2)->default(1000.00); // Minimum points needed to withdraw
            $table->integer('signup_points')->default(100); // Points for new user registration
            $table->integer('referral_points')->default(500); // Points for successful referral
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_settings');
    }
}; 