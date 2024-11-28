<?php

namespace App\Actions\Languages;

use App\Models\Language;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateLanguageAction
{
    use AsAction;

    public function handle(array $data, Language $language = null)
    {
        if (is_null($language)) {
            $language = Language::create($data);
        } else {
            $language->update($data);
        }

        return $language;
    }
}
