<?php

namespace App\Actions\Pages;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Models\Page;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateHomePageAction
{
    use AsAction;

    public function handle(array $data)
    {
        if (!empty($data['title']) && is_array($data['title'])) {
            $data['title'] = json_encode($data['title']);
        }
        if (!empty($data['brief']) && is_array($data['brief'])) {
            $data['brief'] = json_encode($data['brief']);
        }
        if (!empty($data['content']) && is_array($data['content'])) {
            $data['content'] = json_encode($data['content']);
        }
        if (!empty($data['keywords']) && is_array($data['keywords'])) {
            $data['keywords'] = json_encode($data['keywords']);
        }
        flush();

        return Page::where('menu', PageMenuEnum::HOME)
            ->where('type', PageTypeEnum::SYSTEM)
            ->update($data);
    }
}
