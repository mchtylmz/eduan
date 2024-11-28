<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateReviewAction
{
    use AsAction;

    public function handle(array $data, Exam $exam = null)
    {
        $data['ip'] = request()->getClientIp();

        return $exam->reviews()->create($data);
    }
}
