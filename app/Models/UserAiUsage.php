<?php

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;

class UserAiUsage extends Model
{
    use Loggable;

    protected $fillable = ['user_id', 'answer_ai_id', 'usage', 'remaining', 'usage_date'];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AnswerAI::class, 'answer_ai_id', 'id');
    }
}
