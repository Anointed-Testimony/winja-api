<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('application_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('applied'); // applied, shortlisted, rejected, accepted
            $table->text('notes')->nullable();
            $table->string('application_link')->nullable();
            $table->timestamp('applied_at');
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();

            // Ensure a user can't apply to the same opportunity twice
            $table->unique(['user_id', 'opportunity_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('application_trackers');
    }
}; 