<?php

namespace App\Actions\Lessons;

use App\Models\Language;
use App\Models\Lesson;
use App\Models\Role;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateLessonAction
{
    use AsAction;

    public function handle(array $data, Lesson $lesson = null)
    {
        if (!empty($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }

        if (is_null($lesson)) {
            $lesson = Lesson::create($data);
        } else {
            $lesson->update($data);
        }

       resetCache();

        return $lesson;
    }
}
