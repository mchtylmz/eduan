<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Blog extends Model
{
    /** @use HasFactory<\Database\Factories\BlogFactory> */
    use HasFactory, StatusScope, Loggable;

    public $fillable = ['locale', 'slug', 'image', 'title', 'brief', 'content', 'keywords', 'hits', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'published_at' => 'datetime',
            'status' => StatusEnum::class,
        ];
    }

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }
}
