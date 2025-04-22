<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    /** @use HasFactory<\Database\Factories\TestFactory> */
    use HasFactory, softDeletes, StatusScope, DefaultOrderBy, Loggable;

    protected static string $orderByColumn = 'id';

    protected $guarded = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'status' => StatusEnum::class
        ];
    }

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TestsSection::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TestsQuestion::class);
    }
}
