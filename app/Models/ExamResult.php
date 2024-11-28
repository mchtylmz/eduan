<?php

namespace App\Models;

use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    /** @use HasFactory<\Database\Factories\ExamResultFactory> */
    use HasFactory, Loggable;

    public $fillable = ['user_id', 'exam_id','question_count','correct_count','incorrect_count','time', 'completed'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'completed' => YesNoEnum::class
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamResultDetail::class);
    }

}
