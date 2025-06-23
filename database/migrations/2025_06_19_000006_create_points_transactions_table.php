<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['earned', 'spent']); // earned or spent
            $table->integer('amount'); // Points amount
            $table->string('source'); // signup, referral, withdrawal, etc.
            $table->text('description'); // Human readable description
            $table->json('metadata')->nullable(); // Additional info (earnings in naira, point value at time, etc.)
            $table->timestamps();

            // Index for faster queries
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_transactions');
    }
}; 