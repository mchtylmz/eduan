<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_sources', function (Blueprint $table) {
            $table->id();
            $table->char('identity', 64)->unique();
            $table->string('key')->nullable()->index();
            $table->string('kind', 16);
            $table->string('group')->nullable()->index();
            $table->text('original')->nullable();
            $table->string('file', 512);
            $table->unsignedInteger('line');
            $table->string('surface', 16)->index();
            $table->json('routes');
            $table->string('call', 32);
            $table->boolean('active')->default(true)->index();
            $table->char('scan', 32);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_sources');
    }
};
