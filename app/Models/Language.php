<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    /** @use HasFactory<\Database\Factories\LanguageFactory> */
    use HasFactory, StatusScope, Loggable;

    protected $fillable = ['code', 'name', 'sort', 'status'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'status' => StatusEnum::class
        ];
    }

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Translation::class, 'locale', 'code');
    }
}
