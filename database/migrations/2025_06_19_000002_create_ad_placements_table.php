<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->onDelete('cascade');
            $table->enum('placement_type', ['featured', 'inline']); // featured = top picks, inline = for you
            $table->integer('display_position')->default(0); // for inline ads: every 5th position
            $table->boolean('is_active')->default(true);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->timestamp('last_displayed')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['placement_type', 'is_active']);
            $table->index('ad_campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
    }
}; 