<?php

namespace App\Actions\Tests;

use App\Models\Test;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateAction
{
    use AsAction;

    public function handle(array $data, Test $test = null)
    {
        if (empty($data['created_by'])) {
            $data['created_by'] = request()->user()?->id;
        }

        if (!empty($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }

        if (is_null($test)) {
            $test = Test::create($data);
        } else {
            $test->update($data);
        }

        resetCache();

        return $test;
    }
}
