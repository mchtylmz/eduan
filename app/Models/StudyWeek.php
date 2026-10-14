<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyWeek extends Model
{
    protected $fillable = ['starts_on', 'ends_on'];

    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }

    public function scopeForSeason(Builder $query, Season $season): void
    {
        $query->where('starts_on', '<=', $season->end_date->toDateString())
            ->where('ends_on', '>=', $season->start_date->toDateString());
    }

    public function targets(): HasMany
    {
        return $this->hasMany(StudyWeeklyTarget::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(StudyActivityEntry::class);
    }
}
