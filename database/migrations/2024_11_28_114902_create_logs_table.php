<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->dateTime('log_date');
            $table->string('table_name',50)->nullable();
            $table->string('log_type',50);
            $table->string('ip')->nullable();
            $table->longText('agent')->nullable();
            $table->longText('browser')->nullable();
            $table->longText('device')->nullable();
            $table->unsignedBigInteger('data_id')->default(0);
            $table->longText('data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
