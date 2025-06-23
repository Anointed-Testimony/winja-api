<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsored_opportunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opportunity_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('ad_campaign_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'expired'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('sponsored_from')->nullable();
            $table->timestamp('sponsored_to')->nullable();
            $table->timestamps();

            $table->foreign('opportunity_id')->references('id')->on('opportunities')->onDelete('cascade');
            $table->foreign('partner_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('ad_campaign_id')->references('id')->on('ad_campaigns')->onDelete('set null');
            
            // Prevent duplicate sponsorships for the same opportunity
            $table->unique('opportunity_id', 'unique_opportunity_sponsorship');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsored_opportunities');
    }
}; 