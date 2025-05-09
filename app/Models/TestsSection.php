<?php

namespace App\Models;

use App\Enums\TestSectionTypeEnum;
use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use App\Traits\Scope\ParentScope;
use App\Traits\Scope\TestTypeScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Zoha\Metable;

class TestsSection extends Model
{
    use HasFactory, DefaultOrderBy, Loggable, Metable, ParentScope, TestTypeScope;

    protected string $metaTable = 'tests_sections_meta';

    protected static string $orderByColumn = 'order';
    protected static string $orderByColumnDirection = 'ASC';

    protected $guarded = [
        'created_at', 'updated_at'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'type' => TestSectionTypeEnum::class
        ];
    }

    public function test(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function parents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TestsSection::class, 'parent_id', 'id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TestsSection::class, 'parent_id', 'id');
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TestsQuestion::class, 'section_id', 'id');
    }
}
