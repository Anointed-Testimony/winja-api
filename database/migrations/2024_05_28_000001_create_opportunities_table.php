<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('sponsor');
            $table->text('description');
            $table->string('eligibility')->nullable();
            $table->string('status')->default('Active');
            $table->date('expiry')->nullable();
            $table->boolean('verified')->default(false);
            $table->unsignedBigInteger('opportunity_type_id');
            $table->string('image')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('opportunity_type_id')->references('id')->on('opportunity_types')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('opportunities');
    }
}; 