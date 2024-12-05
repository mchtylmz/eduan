<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateExamFavoriteForUserAction
{
    use AsAction;

    public function handle(Exam $exam, User $user, string $action = null): Exam
    {
        match ($action) {
            'attach' => $exam->favorites()->attach($user->id),
            'detach' => $exam->favorites()->detach($user->id)
        };

       resetCache();

        return $exam;
    }
}
