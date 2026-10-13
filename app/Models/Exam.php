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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\hasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use DefaultOrderBy, HasFactory, Loggable, softDeletes, StatusScope;

    protected static string $orderByColumn = 'id';

    protected $guarded = [
        'created_at', 'updated_at', 'deleted_at',
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

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exams_questions')->orderBy('order');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'exams_questions')->orderBy('order');
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'exams_questions')->orderBy('order');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exams_favorites');
    }

    public function result(): hasOne
    {
        return $this->hasOne(ExamResult::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ExamReview::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function userResults(): HasMany
    {
        return $this->hasMany(ExamResult::class)->where('user_id', auth()->id())->groupBy('exam_id');
    }

    public function userResultsWithoutGroupBy(): HasMany
    {
        return $this->hasMany(ExamResult::class)->where('user_id', auth()->id());
    }

    public function publicReviewsCount(): int
    {
        return $this->reviews()->whereIn('visibility', auth()->check()
            ? [ReviewVisibilityEnum::PUBLIC, ReviewVisibilityEnum::PRIVATE]
            : [ReviewVisibilityEnum::PUBLIC])->count();
    }
}
