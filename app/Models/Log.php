<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    public $fillable = ['user_id', 'log_date', 'table_name', 'log_type', 'ip', 'data_id', 'data'];

    public $timestamps = false;
}
