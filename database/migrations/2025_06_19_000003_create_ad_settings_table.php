<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('ad_type', ['featured', 'inline']);
            $table->enum('duration_type', ['daily', 'weekly']);
            $table->decimal('price', 10, 2); // ₦2k daily, ₦7k weekly
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable(); // e.g., "Featured ad in top picks section"
            $table->timestamps();
            
            // Ensure unique combination of ad_type and duration_type
            $table->unique(['ad_type', 'duration_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_settings');
    }
}; 