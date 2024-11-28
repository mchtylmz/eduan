<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Topic extends Model
{
    use HasFactory, softDeletes, StatusScope, HasTranslations, DefaultOrderBy, Loggable;

    protected static string $orderByColumn = 'sort';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'lesson_id', 'title', 'description', 'sort', 'hits', 'status'];

    /**
     * Translatable attributes names.
     *
     * @var array
     */
    protected $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'status' => StatusEnum::class
        ];
    }

    public function getDisplayTitleAttribute(): string
    {
        return sprintf(
            '(%s) %s',
            $this->code,
            $this->title
        );
    }

    public function lesson(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function exams(): \Illuminate\Database\Eloquent\Relations\belongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exams_questions');
    }

    public function userResults(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(ExamResultDetail::class)
            ->groupBy('exam_result_id')
            ->whereHas('examResult', fn ($query) => $query->where('user_id', auth()->id()));
    }
}
