<?php

namespace App\Models;

use App\Enums\ReviewVisibilityEnum;
use App\Enums\YesNoEnum;
use App\Traits\DefaultOrderBy;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;

class ExamReview extends Model
{
    use Loggable, DefaultOrderBy;

    protected string $orderByColumn = 'id';

    public $fillable = ['user_id', 'exam_id', 'comment', 'ip', 'visibility', 'reply_id', 'has_read'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'visibility' => ReviewVisibilityEnum::class,
            'has_read' => YesNoEnum::class,
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function reply(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(ExamReview::class, 'reply_id', 'id');
    }
}
