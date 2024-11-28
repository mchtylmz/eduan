<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    /** @use HasFactory<\Database\Factories\FaqFactory> */
    use HasFactory, StatusScope, Loggable;

    protected $fillable = ['slug', 'title', 'brief', 'content', 'type', 'status', 'sort'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'status' => StatusEnum::class
        ];
    }

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }
}
