<?php

namespace App\Models;

use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestsResult extends Model
{
    /** @use HasFactory<\Database\Factories\TestsResultFactory> */
    use HasFactory, Loggable;

    public $fillable = ['user_id', 'test_id','question_count','correct_count','incorrect_count','duration','point','passing_score','completed', 'completed_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed' => YesNoEnum::class
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TestsResultDetail::class, 'tests_result_id');
    }
}
