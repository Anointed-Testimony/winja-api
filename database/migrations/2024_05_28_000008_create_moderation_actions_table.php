<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->morphs('actionable'); // For polymorphic relationship (listings, users, etc.)
            $table->foreignId('moderator_id')->constrained('users');
            $table->enum('action_type', ['warn', 'remove', 'ban', 'restore', 'dismiss']);
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // For additional data like ban duration, etc.
            $table->timestamp('expires_at')->nullable(); // For temporary actions like bans
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('moderation_actions');
    }
}; 