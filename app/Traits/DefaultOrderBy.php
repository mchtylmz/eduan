<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait DefaultOrderBy
{
    protected static function bootDefaultOrderBy(): void
    {
        if (empty(self::$orderByColumn)) {
            return;
        }

        $class = get_called_class();
        $table = (new $class())->getTable();

        $column = self::$orderByColumn;

        $direction = self::$orderByColumnDirection ?? 'DESC';

        static::addGlobalScope('default_order_by', function (Builder $builder) use ($table, $column, $direction) {
            if (str_contains($column, $table.'.')) {
                $builder->orderBy($column, $direction);
            } else {
                $builder->orderBy($table.'.'.$column, $direction);
            }
        });
    }
}
