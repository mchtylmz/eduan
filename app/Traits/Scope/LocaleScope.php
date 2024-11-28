<?php

namespace App\Traits\Scope;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Builder;

trait LocaleScope
{
    public function scopeLang(Builder $query, ?string $value = null): void
    {
        $query->where('lang', $value ?? app()->getLocale());
    }

    public function scopeLocale(Builder $query, ?string $value = null): void
    {
        $query->where('locale', $value ?? app()->getLocale());
    }
}
