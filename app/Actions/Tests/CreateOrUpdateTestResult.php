<?php

namespace App\Actions\Tests;

use App\Enums\YesNoEnum;
use App\Models\TestsResult;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateTestResult
{
    use AsAction;

    public function handle(array $attributes, array $values = [])
    {
        resetCache();

        return TestsResult::updateOrCreate($attributes, $values);
    }
}
