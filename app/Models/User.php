<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Traits\Loggable;
use App\Traits\Scope\RoleScope;
use App\Traits\Scope\StatusScope;
use Filterable\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Zoha\Metable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, softDeletes, StatusScope, RoleScope, Metable, Loggable;

    protected $guarded = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password' => 'hashed',
            'status' => StatusEnum::class,
            'email_verified' => YesNoEnum::class,
        ];
    }

    public $appends = [
        'display_name'
    ];

    public function getDisplayNameAttribute(): string
    {
        return sprintf('%s %s', $this->name, $this->surname);
    }

    public function favoriteExams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exams_favorites');
    }

    public function aiUsages(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(UserAiUsage::class)->orderByDesc('id');
    }

    public function examResults(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(ExamResult::class)->orderByDesc('id');
    }

    public function solvedExams(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(ExamResult::class)->orderByDesc('id');
    }

    public function testResults(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(TestsResult::class)->orderByDesc('id');
    }

    public function usageGptLimit(string $date = null): int
    {
        $date = dateFormat($date ?? now(), 'Y-m-d');

        return $this->aiUsages()->where('usage_date', $date)->count();
    }
}
