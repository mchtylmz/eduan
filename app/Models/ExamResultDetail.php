<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResultDetail extends Model
{
    /** @use HasFactory<\Database\Factories\ExamResultDetailFactory> */
    use HasFactory, Loggable;

    public $fillable = ['exam_result_id','question_id','answer_id','correct','lesson_id','topic_id','time'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'correct' => YesNoEnum::class
        ];
    }

    public function examResult(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(ExamResult::class);
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function lesson(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function topic(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function answer(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
