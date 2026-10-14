<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_weeks', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on')->unique();
            $table->date('ends_on')->index();
            $table->timestamps();
        });
        Schema::create('study_weekly_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('study_week_id')->constrained('study_weeks')->restrictOnDelete();
            $table->unsignedInteger('questions')->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedInteger('minutes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'study_week_id']);
        });
        Schema::create('study_activity_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('study_week_id')->constrained('study_weeks')->restrictOnDelete();
            $table->foreignId('recorded_by_id')->constrained('users')->restrictOnDelete();
            $table->date('activity_date');
            $table->unsignedInteger('questions')->default(0);
            $table->unsignedInteger('pages')->default(0);
            $table->unsignedInteger('minutes')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'activity_date']);
            $table->index(['study_week_id', 'user_id']);
        });
        foreach (['target' => 'study_weekly_targets', 'activity' => 'study_activity_entries'] as $kind => $parent) {
            Schema::create('study_'.$kind.'_revisions', function (Blueprint $table) use ($kind, $parent) {
                $table->id();
                $table->foreignId($kind.'_id')->constrained($parent)->restrictOnDelete();
                $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
                $table->string('operation', 16);
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        foreach (['study_activity_revisions', 'study_target_revisions', 'study_activity_entries', 'study_weekly_targets', 'study_weeks'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
