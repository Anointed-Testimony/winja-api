<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('icon');
            $table->string('type'); // referral, achievement, special
            $table->json('requirements')->nullable(); // Store requirements as JSON
            $table->integer('points_value')->default(0);
            $table->boolean('is_special')->default(false);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('badge_id')->constrained()->onDelete('cascade');
            $table->timestamp('earned_at');
            $table->json('metadata')->nullable(); // Store additional data about how the badge was earned
            $table->timestamps();

            // Ensure a user can't earn the same badge twice
            $table->unique(['user_id', 'badge_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
}; 