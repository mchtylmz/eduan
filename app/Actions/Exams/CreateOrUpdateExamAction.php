<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateExamAction
{
    use AsAction;

    public function handle(array $data, Exam $exam = null)
    {
        if (empty($data['created_by'])) {
            $data['created_by'] = request()->user()?->id;
        }

        if (!empty($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }

        if (is_null($exam)) {
            $exam = Exam::create($data);
        } else {
            $exam->update($data);
        }

        flush();

        return $exam;
    }
}
