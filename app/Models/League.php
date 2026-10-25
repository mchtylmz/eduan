<?php

namespace App\Models;

use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    /** @use HasFactory<FaqFactory> */
    use DefaultOrderBy, HasFactory, Loggable;

    protected $fillable = ['code', 'label', 'calculated_at', 'start_at', 'end_at', 'is_live', 'calculation_settings', 'calculated_through_at', 'closed_at'];

    protected static string $orderByColumn = 'id';

    protected function casts(): array
    {
        return [
            'is_live' => 'boolean',
            'calculation_settings' => 'array',
            'calculated_at' => 'datetime',
            'calculated_through_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(LeagueResult::class);
    }

    public function calculationStatus(): string
    {
        if (! $this->is_live) {
            return 'single';
        }
        if ($this->closed_at !== null) {
            return 'closed';
        }
        if ($this->start_at?->isFuture()) {
            return 'scheduled';
        }

        return $this->end_at?->isPast() ? 'finalizing' : 'live';
    }
}
