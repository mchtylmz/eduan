<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateMessageAction
{
    use AsAction;

    public function handle(array $data)
    {
        cache()->forget('data_countContactMessageNotRead');

        return Contact::create([
            ...$data,
            'ip' => request()->ip(),
            'locale' => app()->getLocale(),
        ]);
    }
}
