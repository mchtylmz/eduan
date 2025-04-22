<?php

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestsQuestion extends Model
{
    use HasFactory, Loggable;

    public $fillable = ['test_id','section_id','question_id','lesson_id','topic_id','order'];

    public $timestamps = false;

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id', 'id');
    }

    public function section(): BelongsTo
    {
        return  $this->belongsTo(TestsSection::class,  'section_id', 'id');
    }
}
