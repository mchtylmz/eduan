<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, softDeletes, StatusScope, DefaultOrderBy, Loggable;

    protected static string $orderByColumn = 'sort';

    protected $fillable = ['lesson_id', 'topic_id', 'locale', 'code', 'title', 'attachment', 'solution', 'sort', 'time', 'status'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'status' => StatusEnum::class
        ];
    }

    public $appends = [
        'attachment_url',
        'solution_url',
    ];

    public function getAttachmentUrlAttribute(): string
    {
        if ($this->attachment && file_exists(public_path($this->attachment))) {
            return $this->attachment;
        }

        return settings()->siteLogo;
    }

    public function getSolutionUrlAttribute(): string
    {
        if ($this->solution && file_exists(public_path($this->solution))) {
            return $this->solution;
        }

        return settings()->siteLogo;
    }

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }


    public function lesson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function topic(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function answers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->HasMany(Answer::class);
    }
}
