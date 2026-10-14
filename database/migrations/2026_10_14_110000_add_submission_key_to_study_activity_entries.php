<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_activity_entries', function (Blueprint $table) {
            $table->uuid('submission_key')->nullable();
            $table->unique(['user_id', 'submission_key'], 'study_activity_submission_unique');
        });
    }

    public function down(): void
    {
        Schema::table('study_activity_entries', function (Blueprint $table) {
            $table->dropUnique('study_activity_submission_unique');
            $table->dropColumn('submission_key');
        });
    }
};
