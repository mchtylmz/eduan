<?php

namespace App\Models;

use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    /** @use HasFactory<\Database\Factories\FaqFactory> */
    use HasFactory, DefaultOrderBy, Loggable;

    protected $fillable = ['code', 'label', 'calculated_at', 'start_at', 'end_at'];

    protected static string $orderByColumn = 'id';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function results(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeagueResult::class);
    }
}
