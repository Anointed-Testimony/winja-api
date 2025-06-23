<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained('opportunities')->onDelete('cascade');
            $table->enum('ad_type', ['featured', 'inline']); // featured = top picks, inline = for you section
            $table->enum('duration_type', ['daily', 'weekly']); // daily = ₦2k, weekly = ₦7k
            $table->integer('duration_value'); // number of days/weeks
            $table->decimal('amount_paid', 10, 2); // actual amount paid
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'expired'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->json('payment_details')->nullable();
            $table->text('admin_notes')->nullable(); // for admin approval/rejection notes
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['partner_id', 'status']);
            $table->index(['ad_type', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
}; 