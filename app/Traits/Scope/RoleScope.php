<?php

namespace App\Traits\Scope;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Builder;

trait RoleScope
{
    public function scopeAdmin(Builder $query): void
    {
        $query->permission(\App\Enums\RoleTypeEnum::ADMIN);
    }

    public function scopeUser(Builder $query): void
    {
        $query->permission(\App\Enums\RoleTypeEnum::USER)->permission('exams:solve', true);
    }

    public function scopePremiumUser(Builder $query): void
    {
        $query->permission(\App\Enums\RoleTypeEnum::USER)->permission('exams:solve');
    }
}
