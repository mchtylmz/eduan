<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    use Loggable;

    protected $fillable = ['locale', 'key', 'value'];

    public $timestamps = false;

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }
}
