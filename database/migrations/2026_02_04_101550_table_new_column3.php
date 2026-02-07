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
        Schema::table('tests_results', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')
                ->default(1)
                ->nullable(1)
                ->after('id');
        });
        Schema::table('exam_results', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')
                ->default(1)
                ->nullable(1)
                ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests_results', function (Blueprint $table) {
            $table->dropColumn('season_id');
        });
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn('season_id');
        });

    }
};
