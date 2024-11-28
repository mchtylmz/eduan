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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id')->index();
            $table->unsignedBigInteger('topic_id')->index();
            $table->string('locale', 5)->index();
            $table->string('code')->nullable();
            $table->text('title')->nullable();
            $table->string('attachment')->nullable();
            $table->string('solution');
            $table->integer('sort')->default(1)->index();
            $table->integer('time')->default(300)->index();
            $table->string('status')->default(\App\Enums\StatusEnum::ACTIVE)->index();
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
