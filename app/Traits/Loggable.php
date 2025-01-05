<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\DB;

trait Loggable
{
    public static function insertLog($model, string $logType = 'none'): void
    {
        if (!auth()->check()) {
            return;
        }

        Log::insert([
            'user_id'    => auth()->id(),
            'log_date'   => now(),
            'table_name' => $model->getTable(),
            'log_type'   => $logType,
            'ip'         => request()->ip(),
            'data_id'    => $model->id ?? 0,
            'data'       => json_encode($logType == 'create' ? $model : $model->getRawOriginal()),
            'agent'      => request()->server('HTTP_USER_AGENT'),
            'browser'    => request()->server('HTTP_SEC_CH_UA'),
            'device'     => request()->server('HTTP_SEC_CH_UA_PLATFORM')
        ]);
    }

    public static function bootLoggable(): void
    {
        self::updated(function ($model) {
            resetCache();
            self::insertLog($model, 'edit');
        });


        self::deleted(function ($model) {
            self::insertLog($model, 'delete');
            resetCache();
        });


        self::created(function ($model) {
            self::insertLog($model, 'create');
            resetCache();
        });
    }

    public function customLog(string $table, string $logType, array $data = []): void
    {
        Log::insert([
            'user_id'    => auth()->check() ? auth()->id() : 0,
            'log_date'   => now(),
            'table_name' => $table,
            'log_type'   => $logType,
            'ip'         => request()->ip(),
            'data_id'    => $data['id'] ?? 0,
            'data'       => json_encode($data),
            'agent'      => request()->server('HTTP_USER_AGENT'),
            'browser'    => request()->server('HTTP_SEC_CH_UA'),
            'device'     => request()->server('HTTP_SEC_CH_UA_PLATFORM')
        ]);
    }
}
