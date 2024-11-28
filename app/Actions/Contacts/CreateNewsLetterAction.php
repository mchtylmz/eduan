<?php

namespace App\Actions\Contacts;

use App\Models\Newsletter;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateNewsLetterAction
{
    use AsAction;

    public function handle(array $data)
    {
        flush();

        return Newsletter::create([
            ...$data,
            'ip' => request()->ip(),
            'token' => Str::random(60)
        ]);
    }
}
