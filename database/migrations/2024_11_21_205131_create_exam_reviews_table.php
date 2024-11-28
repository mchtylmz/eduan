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
        Schema::create('exam_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('comment');
            $table->string('visibility')->default(\App\Enums\ReviewVisibilityEnum::PRIVATE)->index();
            $table->unsignedBigInteger('reply_id')->default(0)->index();
            $table->tinyInteger('has_read')->default(\App\Enums\YesNoEnum::NO)->index();
            $table->string('ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_reviews');
    }
};
