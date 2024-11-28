<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use App\Traits\Scope\StatusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Lesson extends Model
{
    use HasFactory, softDeletes, StatusScope, HasTranslations, DefaultOrderBy, Loggable;

    protected static string $orderByColumn = 'sort';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'description', 'sort', 'hits', 'status'];

    /**
     * Translatable attributes names.
     *
     * @var array
     */
    protected array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'status' => StatusEnum::class
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return sprintf(
            '(%s) %s',
            $this->code,
            $this->name
        );
    }

    public function topics(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function exams(): \Illuminate\Database\Eloquent\Relations\belongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exams_questions');
    }
}
