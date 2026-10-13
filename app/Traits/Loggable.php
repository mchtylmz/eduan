<?php

namespace App\Traits;

use App\Models\Log;

trait Loggable
{
    public static function insertLog($model, string $logType = 'none'): void
    {
        if (! auth()->check()) {
            return;
        }

        Log::insert([
            'user_id' => auth()->id(),
            'log_date' => now(),
            'table_name' => $model->getTable(),
            'log_type' => $logType,
            'ip' => request()->ip(),
            'data_id' => $model->id ?? 0,
            'data' => json_encode($logType == 'create' ? $model : $model->getRawOriginal()),
            'agent' => request()->server('HTTP_USER_AGENT'),
            'browser' => request()->server('HTTP_SEC_CH_UA'),
            'device' => request()->server('HTTP_SEC_CH_UA_PLATFORM'),
        ]);
    }

    public static function bootLoggable(): void
    {
        self::updated(function ($model) {
            self::resetModelCache($model);
            self::insertLog($model, 'edit');
        });

        self::deleted(function ($model) {
            self::insertLog($model, 'delete');
            self::resetModelCache($model, true);
        });

        self::created(function ($model) {
            self::insertLog($model, 'create');
            self::resetModelCache($model);
        });
    }

    private static function resetModelCache($model, bool $deleted = false): void
    {
        // Public form submissions and view counters must not clear every page's cache/compiled views.
        if (in_array($model->getTable(), ['contacts', 'newsletters'])) {
            if ($model->getTable() === 'contacts') {
                cache()->forget('data_countContactMessageNotRead');
            }

            return;
        }
        if (! $deleted && $model->wasChanged() && ! array_diff(array_keys($model->getChanges()), ['hits', 'updated_at'])) {
            return;
        }
        resetCache();
    }

    public function customLog(string $table, string $logType, array $data = []): void
    {
        Log::insert([
            'user_id' => auth()->check() ? auth()->id() : 0,
            'log_date' => now(),
            'table_name' => $table,
            'log_type' => $logType,
            'ip' => request()->ip(),
            'data_id' => $data['id'] ?? 0,
            'data' => json_encode($data),
            'agent' => request()->server('HTTP_USER_AGENT'),
            'browser' => request()->server('HTTP_SEC_CH_UA'),
            'device' => request()->server('HTTP_SEC_CH_UA_PLATFORM'),
        ]);
    }
}
