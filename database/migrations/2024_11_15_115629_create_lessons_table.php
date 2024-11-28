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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->text('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('sort')->default(1)->index();
            $table->unsignedBigInteger('hits')->default(0)->index();
            $table->string('status', 12)->default(\App\Enums\StatusEnum::ACTIVE)->index();
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
