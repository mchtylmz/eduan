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
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->tinyInteger('accept_terms')->default(1);
            $table->string('ip', 30)->default(0);
            $table->string('token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('newsletters_mails', function (Blueprint $table) {
            $table->string('locale', 5)->index();
            $table->string('email')->index();
            $table->longText('content')->nullable();
            $table->timestamp('sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletters');
        Schema::dropIfExists('newsletters_mails');
    }
};
