<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyWeeklyTarget extends Model
{
    protected $fillable = ['user_id', 'study_week_id', 'questions', 'pages', 'minutes'];

    protected function casts(): array
    {
        return ['questions' => 'integer', 'pages' => 'integer', 'minutes' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(StudyWeek::class, 'study_week_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(StudyTargetRevision::class, 'target_id');
    }
}
