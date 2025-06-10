<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('age_group')->nullable();
            $table->string('geo_location')->nullable();
            $table->string('academic_level')->nullable();
            $table->json('interests')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->string('referral_code')->unique()->nullable();
            $table->string('referred_by')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->enum('user_type', ['user', 'admin', 'partner'])->default('user');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
