<?php

namespace App\Traits\Scope;

use Illuminate\Database\Eloquent\Builder;

trait ParentScope
{
    public function scopeParent(Builder $query, int $parent_id): void
    {
        $query->where('parent_id', $parent_id);
    }

    public function scopeOrParent(Builder $query, int $parent_id): void
    {
        $query->orWhere('parent_id', $parent_id);
    }

    public function scopeParentIsZero(Builder $query): void
    {
        $query->where('parent_id', 0);
    }

    public function scopeOrParentIsZero(Builder $query): void
    {
        $query->orWhere('parent_id', 0);
    }
}
