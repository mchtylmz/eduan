<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyNoteRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['note_id', 'actor_id', 'operation', 'before', 'after'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'immutable_datetime'];
    }
}
