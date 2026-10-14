<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyActivityEntry extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'study_week_id', 'recorded_by_id', 'activity_date', 'questions', 'pages', 'minutes'];

    protected function casts(): array
    {
        return ['activity_date' => 'immutable_date', 'questions' => 'integer', 'pages' => 'integer', 'minutes' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(StudyWeek::class, 'study_week_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(StudyActivityRevision::class, 'activity_id');
    }
}
