<?php

namespace App\Actions\Pages;

use App\Models\Lesson;
use App\Models\Page;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdatePageAction
{
    use AsAction;

    public function handle(array $data, Page $page = null)
    {
        if (is_null($page)) {
            $page = Page::create($data);
        } else {
            $page->update($data);
        }

       resetCache();

        return $page;
    }
}
