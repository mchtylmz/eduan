<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    /** @use HasFactory<\Database\Factories\AnswerFactory> */
    use HasFactory, softDeletes, StatusScope, Loggable;

    protected $fillable = ['question_id', 'title', 'correct'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'correct' => YesNoEnum::class
        ];
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
