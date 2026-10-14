<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyTargetRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['target_id', 'actor_id', 'operation', 'before', 'after'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'immutable_datetime'];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(StudyWeeklyTarget::class, 'target_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
