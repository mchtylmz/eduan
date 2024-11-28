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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->index();
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->string('title');
            $table->string('brief')->nullable();
            $table->longText('content')->nullable();
            $table->string('keywords')->nullable();
            $table->bigInteger('hits')->default(0);
            $table->string('status')->default(\App\Enums\StatusEnum::ACTIVE);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
