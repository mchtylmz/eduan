<?php

namespace App\Traits\Scope;

use App\Enums\TestSectionTypeEnum;
use Illuminate\Database\Eloquent\Builder;

trait TestTypeScope
{
    public function scopeTopic(Builder $query): void
    {
        $query->where('type', TestSectionTypeEnum::TOPIC);
    }

    public function scopeOrTopic(Builder $query): void
    {
        $query->orWhere('type', TestSectionTypeEnum::TOPIC);
    }

    public function scopeContent(Builder $query): void
    {
        $query->where('type', TestSectionTypeEnum::CONTENT);
    }

    public function scopeOrContent(Builder $query): void
    {
        $query->orWhere('type', TestSectionTypeEnum::CONTENT);
    }

    public function scopePdf(Builder $query): void
    {
        $query->where('type', TestSectionTypeEnum::PDF);
    }

    public function scopeOrPdf(Builder $query): void
    {
        $query->orWhere('type', TestSectionTypeEnum::PDF);
    }

    public function scopeQuestion(Builder $query): void
    {
        $query->where('type', TestSectionTypeEnum::QUESTION);
    }

    public function scopeOrQuestion(Builder $query): void
    {
        $query->orWhere('type', TestSectionTypeEnum::QUESTION);
    }

    public function scopeType(Builder $query, TestSectionTypeEnum $type): void
    {
        $query->where('type', $type);
    }

    public function scopeOrType(Builder $query, TestSectionTypeEnum $type): void
    {
        $query->orWhere('type', $type);
    }

}
