<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // subscription, withdrawal, etc.
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('currency')->default('NGN');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_wallets');
    }
}; 