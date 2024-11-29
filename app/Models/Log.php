<?php

namespace App\Models;

use App\Traits\DefaultOrderBy;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use DefaultOrderBy;

    protected string $orderByColumn = 'id';

    public $fillable = ['user_id', 'log_date', 'table_name', 'log_type', 'ip', 'data_id', 'data', 'agent', 'browser', 'device'];

    public $timestamps = false;

    public function user(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(User::class);
    }
}
