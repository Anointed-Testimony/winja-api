<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_id')->nullable()->after('sponsor');
            $table->foreign('partner_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn('partner_id');
        });
    }
}; 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 