<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable();
            $table->text('company_description')->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_logo')->nullable();
            $table->timestamp('partner_since')->nullable();
            $table->enum('partner_status', ['active', 'inactive', 'suspended'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_description',
                'company_website',
                'company_logo',
                'partner_since',
                'partner_status'
            ]);
        });
    }
}; 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 