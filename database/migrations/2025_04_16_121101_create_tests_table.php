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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->index();
            $table->string('code')->nullable()->index();
            $table->string('name');
            $table->longText('content')->nullable();
            $table->bigInteger('hits')->default(0);
            $table->string('status')->default(\App\Enums\StatusEnum::ACTIVE);
            $table->integer('duration')->default(600);
            $table->integer('passing_score')->default(60);
            $table->integer('correct_point')->default(3);
            $table->integer('incorrect_point')->default(-1);
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tests_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable(0);
            $table->string('type', 16)->default(\App\Enums\TestSectionTypeEnum::TOPIC)->index();
            $table->text('name')->nullable();
            $table->unsignedBigInteger('order')->default(1)->index();
            $table->timestamps();
        });

        Schema::create('tests_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('test_id')->index();
            $table->unsignedBigInteger('section_id')->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->unsignedBigInteger('lesson_id')->index();
            $table->unsignedBigInteger('topic_id')->index();
            $table->unsignedBigInteger('order')->default(1)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
        Schema::dropIfExists('tests_sections');
        Schema::dropIfExists('tests_questions');
        Schema::dropIfExists('tests');
    }
};
