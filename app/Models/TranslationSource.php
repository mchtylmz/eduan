<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationSource extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['routes' => 'array', 'active' => 'boolean'];
    }
}
