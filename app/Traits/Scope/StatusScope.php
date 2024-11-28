<?php

namespace App\Traits\Scope;

use App\Enums\StatusApproveEnum;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use Illuminate\Database\Eloquent\Builder;

trait StatusScope
{
    public function scopeActive(Builder $query): void
    {
        $query->where('status', StatusEnum::ACTIVE);
    }

    public function scopePassive(Builder $query): void
    {
        $query->where('status', StatusEnum::PASSIVE);
    }

}
