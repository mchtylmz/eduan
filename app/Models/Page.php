<?php

namespace App\Models;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use StatusScope, HasTranslations, Loggable;

    protected $fillable = ['slug', 'title', 'brief', 'content', 'images','keywords', 'sort', 'link', 'menu', 'type', 'status'];

    protected array $translatable = ['title', 'brief', 'content', 'keywords', 'images', 'link'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'menu' => PageMenuEnum::class,
            'type' => PageTypeEnum::class,
            'status' => StatusEnum::class,
        ];
    }
}
