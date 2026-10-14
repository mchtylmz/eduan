<?php

namespace App\Models;

use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use Database\Factories\ExamResultDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsTo;

class ExamResultDetail extends Model
{
    /** @use HasFactory<ExamResultDetailFactory> */
    use HasFactory, Loggable;

    public $fillable = ['exam_result_id', 'question_id', 'answer_id', 'study_answered_at', 'correct', 'lesson_id', 'topic_id', 'time'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'study_answered_at' => 'immutable_datetime',
            'correct' => YesNoEnum::class,
        ];
    }

    public function examResult(): belongsTo
    {
        return $this->belongsTo(ExamResult::class);
    }

    public function question(): belongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function lesson(): belongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function topic(): belongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function answer(): belongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
