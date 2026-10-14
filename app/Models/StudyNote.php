<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'author_id', 'submission_key', 'body', 'student_visible'];

    protected function casts(): array
    {
        return ['student_visible' => 'boolean'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }
}
