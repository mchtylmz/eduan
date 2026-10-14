<?php

namespace App\Actions\Study;

use App\Helpers\StudyInput;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StudyWrite
{
    public static function values(array $input, bool $target = false): array
    {
        try {
            return StudyInput::values($input, $target);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([$exception->getMessage() => __('study.invalid_number')]);
        }
    }

    public static function snapshot(StudyActivityEntry|StudyWeeklyTarget $record): array
    {
        $values = ['questions' => $record->questions, 'pages' => $record->pages, 'minutes' => $record->minutes];
        if ($record instanceof StudyActivityEntry) {
            $values = ['activity_date' => $record->activity_date->toDateString()] + $values;
        }

        return $values;
    }

    public static function lock(User $user): void
    {
        // Every study write for this owner uses the same mutex, including first creation.
        User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
        AuthorizeStudyAccess::run($user, write: true);
    }

    public static function unchanged(?array $expected, array $actual): void
    {
        abort_unless($expected === $actual, 409, __('study.conflict'));
    }
}
