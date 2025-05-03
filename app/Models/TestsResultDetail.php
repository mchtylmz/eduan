<?php

namespace App\Models;

use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestsResultDetail extends Model
{
    /** @use HasFactory<\Database\Factories\TestsResultDetailFactory> */
    use HasFactory, Loggable;

    public $fillable = ['tests_result_id','section_id','question_id','answer_id','correct','lesson_id','topic_id','time'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'correct' => YesNoEnum::class
        ];
    }

    public function testResult(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(TestsResult::class, 'test_result_id', 'id');
    }

    public function section(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(TestsSection::class, 'section_id', 'id');
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
