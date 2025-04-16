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
            $table->integer('duration')->default(600)->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tests_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('test_id')->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
        //Schema::dropIfExists('tests');
    }
};
