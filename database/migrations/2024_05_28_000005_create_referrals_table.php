<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, completed, expired
            $table->json('rewards_claimed')->nullable(); // Track what rewards have been claimed
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            // Ensure a user can't be referred multiple times
            $table->unique('referred_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('referrals');
    }
}; 