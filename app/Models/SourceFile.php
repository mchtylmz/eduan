<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourceFile extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['storage_config', 'path'];

    protected function casts(): array
    {
        return ['storage_config' => 'encrypted:array', 'size' => 'integer', 'uploaded_by' => 'integer', 'ready_at' => 'datetime'];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function viewerUrl(): string
    {
        return route('frontend.sources.show', ['code' => $this->code, 'slug' => $this->slug]);
    }

    public function rawUrl(): string
    {
        return $this->disk === 'r2' ? $this->public_url : asset('uploads/'.$this->path);
    }
}
