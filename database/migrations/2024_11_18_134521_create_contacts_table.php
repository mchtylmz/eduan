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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('school_name')->nullable();
            $table->text('message');
            $table->text('reply')->nullable();
            $table->tinyInteger('accept_terms')->default(0);
            $table->string('ip', 30)->default(0);
            $table->tinyInteger('has_read')->default(\App\Enums\YesNoEnum::NO)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
