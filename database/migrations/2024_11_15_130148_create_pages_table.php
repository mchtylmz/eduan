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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->index();
            $table->longText('title');
            $table->longText('brief')->nullable();
            $table->longText('content')->nullable();
            $table->longText('images')->nullable();
            $table->text('keywords')->nullable();
            $table->tinyInteger('sort')->default(1);
            $table->string('menu')->default(\App\Enums\PageMenuEnum::FOOTER);
            $table->string('type', 10)->default(\App\Enums\PageTypeEnum::SYSTEM)->index();
            $table->string('status', 10)->default(\App\Enums\StatusEnum::ACTIVE)->index();
            $table->text('link')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
