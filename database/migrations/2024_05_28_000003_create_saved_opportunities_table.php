<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('saved_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained()->onDelete('cascade');
            $table->boolean('reminder_set')->default(false);
            $table->timestamp('reminder_date')->nullable();
            $table->timestamps();

            // Ensure a user can't save the same opportunity twice
            $table->unique(['user_id', 'opportunity_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_opportunities');
    }
}; 