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
        Schema::create('league_results', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('league_number')->nullable(0)->index();
            $table->unsignedBigInteger('league_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->bigInteger('total_score')->nullable(0);
            $table->bigInteger('total_correct')->nullable(0);
            $table->bigInteger('total_incorrect')->nullable(0);
            $table->bigInteger('total_duration')->nullable(0);
            $table->bigInteger('position')->nullable(0);
            $table->bigInteger('rank_position')->nullable(0)->index();
            $table->timestamps();

            $table->unique(['league_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('league_results');
    }
};
