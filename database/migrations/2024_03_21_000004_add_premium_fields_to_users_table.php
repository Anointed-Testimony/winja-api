<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('premium_until')->nullable();
            $table->string('whatsapp_number')->nullable();

            // Index for faster premium user queries
            $table->index('is_premium');
            $table->index('premium_until');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_premium', 'premium_until', 'whatsapp_number']);
            $table->dropIndex(['is_premium', 'premium_until']);
        });
    }
}; 