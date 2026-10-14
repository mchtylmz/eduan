<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->uuid('submission_key');
            $table->text('body');
            $table->boolean('student_visible')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['author_id', 'submission_key']);
            $table->index(['user_id', 'student_visible', 'id']);
        });
        Schema::create('study_note_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('study_notes')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('operation', 10);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_note_revisions');
        Schema::dropIfExists('study_notes');
    }
};
