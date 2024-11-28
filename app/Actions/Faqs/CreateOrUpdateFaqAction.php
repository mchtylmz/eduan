<?php

namespace App\Actions\Faqs;

use App\Models\Faq;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateFaqAction
{
    use AsAction;

    public function handle(array $data, Faq $faq = null)
    {
        if (is_null($faq)) {
            $faq = Faq::create($data);
        } else {
            $faq->update($data);
        }

        flush();

        return $faq;
    }
}
