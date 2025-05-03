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
        Schema::create('tests_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('test_id')->index();
            $table->integer('question_count')->default(0)->index();
            $table->integer('correct_count')->default(0)->index();
            $table->integer('incorrect_count')->default(0)->index();
            $table->integer('duration')->default(0)->index();
            $table->tinyInteger('completed')->default(\App\Enums\YesNoEnum::NO)->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests_results');
    }
};
