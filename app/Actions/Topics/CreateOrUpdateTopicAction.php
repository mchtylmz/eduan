<?php

namespace App\Actions\Topics;

use App\Models\Topic;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateTopicAction
{
    use AsAction;

    public function handle(array $data, Topic $topic = null)
    {
        if (!empty($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }

        if (is_null($topic)) {
            $topic = Topic::create($data);
        } else {
            $topic->update($data);
        }

        flush();

        return $topic;
    }
}
