<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->unsignedBigInteger('view_count')->default(0)->after('application_link');
            $table->unsignedBigInteger('click_count')->default(0)->after('view_count');
            $table->unsignedBigInteger('save_count')->default(0)->after('click_count');
            $table->unsignedBigInteger('application_count')->default(0)->after('save_count');
        });
    }

    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['view_count', 'click_count', 'save_count', 'application_count']);
        });
    }
}; 