<?php

namespace App\Models;

use App\Enums\ReviewVisibilityEnum;
use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, softDeletes, StatusScope, DefaultOrderBy, Loggable;

    protected static string $orderByColumn = 'id';

    protected $guarded = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'status' => StatusEnum::class,
            'visibility' => VisibilityEnum::class,
        ];
    }

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exams_questions')->orderBy('order');
    }

    public function lessons(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'exams_questions')->orderBy('order');
    }

    public function topics(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'exams_questions')->orderBy('order');
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exams_favorites');
    }

    public function result(): \Illuminate\Database\Eloquent\Relations\hasOne
    {
        return $this->hasOne(ExamResult::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamReview::class);
    }

    public function results(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function userResults(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamResult::class)->where('user_id', auth()->id())->groupBy('exam_id');
    }

    public function publicReviewsCount(): int
    {
        return $this->reviews()->where('visibility', '!=', ReviewVisibilityEnum::HIDE)->count();
    }
}
