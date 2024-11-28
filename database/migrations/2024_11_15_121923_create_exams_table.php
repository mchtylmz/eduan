<?php

use App\Models\Exam;
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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->index();
            $table->string('code')->nullable()->index();
            $table->string('name');
            $table->longText('content')->nullable();
            $table->tinyInteger('visibility')->default(\App\Enums\VisibilityEnum::PREMIUM);
            $table->bigInteger('hits')->default(0);
            $table->string('status')->default(\App\Enums\StatusEnum::ACTIVE);
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('exams_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->unsignedBigInteger('lesson_id')->index();
            $table->unsignedBigInteger('topic_id')->index();
            $table->unsignedBigInteger('order')->default(1)->index();
        });

        Schema::create('exams_favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('user_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exams_questions');
        Schema::dropIfExists('exams_favorites');
    }
};
