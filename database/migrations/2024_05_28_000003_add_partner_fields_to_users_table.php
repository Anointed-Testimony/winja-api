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
            $table->string('business_registration_number')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('business_address')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_position')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->string('verification_documents')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->integer('verified_by')->nullable();
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
                'partner_status',
                'business_registration_number',
                'tax_identification_number',
                'business_address',
                'contact_person_name',
                'contact_person_position',
                'contact_person_phone',
                'verification_documents',
                'verification_notes',
                'verified_at',
                'verified_by'
            ]);
        });
    }
}; 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 