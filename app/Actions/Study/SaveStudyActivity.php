<?php

namespace App\Actions\Study;

use App\Models\StudyActivityEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveStudyActivity
{
    use AsAction;

    public function handle(User $user, string $date, array $input, string $submissionKey, ?int $entryId = null, ?array $expected = null): StudyActivityEntry
    {
        AuthorizeStudyChange::run($user, 'activity', $date);
        Validator::make(['submission_key' => $submissionKey], ['submission_key' => 'required|uuid'])->validate();
        $values = ['activity_date' => $date] + StudyWrite::values($input);

        return DB::transaction(function () use ($user, $date, $values, $submissionKey, $entryId, $expected) {
            StudyWrite::lock($user);
            AuthorizeStudyChange::run($user, 'activity', $date);
            if ($entryId !== null) {
                $record = StudyActivityEntry::query()->where('user_id', $user->id)->whereKey($entryId)->lockForUpdate()->firstOrFail();
                // A closed record cannot be moved into an open week to bypass its lock.
                AuthorizeStudyChange::run($user, 'activity', $record->activity_date->toDateString());
                $before = StudyWrite::snapshot($record);
                if ($before === $values) {
                    return $record;
                }
                StudyWrite::unchanged($expected, $before);
                $operation = 'update';
            } else {
                $record = StudyActivityEntry::withTrashed()->where('user_id', $user->id)->where('submission_key', $submissionKey)->lockForUpdate()->first();
                if ($record) {
                    abort_unless(! $record->trashed() && StudyWrite::snapshot($record) === $values, 409, __('study.conflict'));

                    return $record;
                }
                $before = null;
                $operation = 'create';
                $record = new StudyActivityEntry(['user_id' => $user->id, 'recorded_by_id' => auth()->id(), 'submission_key' => $submissionKey]);
            }
            $week = EnsureStudyWeek::run($date);
            $record->fill($values + ['study_week_id' => $week->id])->save();
            $record->revisions()->create(['actor_id' => auth()->id(), 'operation' => $operation, 'before' => $before, 'after' => $values]);

            return $record;
        });
    }
}
