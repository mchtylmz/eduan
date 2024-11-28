<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use Loggable;

    protected $fillable = ['locale', 'name', 'email', 'phone', 'school_name', 'message', 'accept_terms', 'ip', 'reply', 'has_read'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'has_read' => YesNoEnum::class
        ];
    }

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }
}
