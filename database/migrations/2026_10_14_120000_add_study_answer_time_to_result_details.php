<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['exam_result_details', 'tests_result_details'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dateTime('study_answered_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['exam_result_details', 'tests_result_details'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('study_answered_at'));
        }
    }
};
