<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyActivityRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['activity_id', 'actor_id', 'operation', 'before', 'after'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'immutable_datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(StudyActivityEntry::class, 'activity_id')->withTrashed();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
