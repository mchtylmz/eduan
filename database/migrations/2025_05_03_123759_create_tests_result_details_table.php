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
        Schema::create('tests_result_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tests_result_id')->index();
            $table->unsignedBigInteger('section_id')->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->unsignedBigInteger('answer_id')->index();
            $table->tinyInteger('correct')->default(\App\Enums\YesNoEnum::NO)->index();
            $table->tinyInteger('point')->default(0);
            $table->unsignedBigInteger('lesson_id')->index();
            $table->unsignedBigInteger('topic_id')->index();
            $table->integer('time')->default(0);
            $table->timestamps();

            $table->unique(['tests_result_id', 'section_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests_result_details');
    }
};
