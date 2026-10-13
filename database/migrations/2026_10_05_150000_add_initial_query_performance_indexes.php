<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('username', 'users_username_index');
        });

        Schema::table('exam_results', function (Blueprint $table) {
            $table->index(['season_id', 'completed', 'updated_at'], 'exam_results_season_completed_updated_index');
        });

        Schema::table('exam_result_details', function (Blueprint $table) {
            $table->index(['exam_result_id', 'question_id'], 'exam_result_details_result_question_index');
        });
    }

    public function down(): void
    {
        Schema::table('exam_result_details', function (Blueprint $table) {
            $table->dropIndex('exam_result_details_result_question_index');
        });

        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropIndex('exam_results_season_completed_updated_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_username_index');
        });
    }
};
