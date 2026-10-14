<?php

namespace App\Models;

use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use Database\Factories\TestsResultDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsTo;

class TestsResultDetail extends Model
{
    /** @use HasFactory<TestsResultDetailFactory> */
    use HasFactory, Loggable;

    public $fillable = ['tests_result_id', 'section_id', 'question_id', 'answer_id', 'study_answered_at', 'correct', 'point', 'lesson_id', 'topic_id', 'time'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'study_answered_at' => 'immutable_datetime',
            'correct' => YesNoEnum::class,
        ];
    }

    public function testResult(): belongsTo
    {
        return $this->belongsTo(TestsResult::class, 'tests_result_id', 'id');
    }

    public function section(): belongsTo
    {
        return $this->belongsTo(TestsSection::class, 'section_id', 'id');
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
