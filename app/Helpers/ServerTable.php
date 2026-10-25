<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServerTable
{
    public static function parameters(Request $request, array $sorts, string $defaultSort, array $filters = []): array
    {
        $parameters = $request->validate([
            'limit' => ['sometimes', 'integer', 'between:1,100'],
            'offset' => ['sometimes', 'integer', 'between:0,1000000'],
            'search' => ['nullable', 'string', 'max:250'],
            'sort' => ['sometimes', Rule::in(array_keys($sorts))],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'refresh' => ['sometimes', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'passive'])],
            ...$filters,
        ]);

        $parameters += ['limit' => 15, 'offset' => 0, 'sort' => $defaultSort, 'order' => 'asc', 'search' => '', 'refresh' => false];
        $parameters['limit'] = (int) $parameters['limit'];
        $parameters['offset'] = (int) $parameters['offset'];
        $parameters['search'] = trim($parameters['search'] ?? '');

        return $parameters;
    }

    public static function page(string $table, Builder $query, array $parameters, array $sorts, callable $row, array $scope = [], bool $cache = true): array
    {
        $load = function () use ($table, $query, $parameters, $sorts, $row) {
            $total = (clone $query)->count();
            $rows = $query->reorder()->orderBy($sorts[$parameters['sort']], $parameters['order'])
                ->orderBy($query->getModel()->getQualifiedKeyName())
                ->offset($parameters['offset'])->limit($parameters['limit'])->get()
                ->map(fn ($record) => ['table' => $table, ...$row($record)])->all();

            return ['total' => $total, 'rows' => $rows];
        };

        return $cache ? TableCache::remember($table, $parameters, $load, $scope) : $load();
    }
}
