<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique(); // Payment gateway transaction ID
            $table->string('type'); // subscription, withdrawal, etc.
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->string('status'); // pending, completed, failed
            $table->string('payment_method')->nullable(); // card, bank transfer, etc.
            $table->json('payment_details')->nullable(); // Additional payment information
            $table->string('reference')->unique(); // Payment reference
            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}; 