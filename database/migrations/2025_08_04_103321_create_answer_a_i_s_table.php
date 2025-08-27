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
        Schema::create('answer_a_i_s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('lesson_id')->index();
            $table->unsignedBigInteger('topic_id')->index();
            $table->string('locale', 5)->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->tinyInteger('report')->default(\App\Enums\YesNoEnum::NO)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_a_i_s');
    }
};
