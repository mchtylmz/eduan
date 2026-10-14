<?php

namespace App\Actions\Study;

use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveStudyTarget
{
    use AsAction;

    public function handle(User $user, string $date, array $input, ?array $expected = null): StudyWeeklyTarget
    {
        AuthorizeStudyChange::run($user, 'target', $date);
        $values = StudyWrite::values($input, target: true);

        return DB::transaction(function () use ($user, $date, $values, $expected) {
            StudyWrite::lock($user);
            AuthorizeStudyChange::run($user, 'target', $date);
            $week = EnsureStudyWeek::run($date);
            $record = StudyWeeklyTarget::query()->where('user_id', $user->id)->where('study_week_id', $week->id)->lockForUpdate()->first();
            $before = $record ? StudyWrite::snapshot($record) : null;
            if ($record && $before === $values) {
                return $record;
            }
            abort_unless($before === $expected, 409, __('study.conflict'));
            $operation = $record ? 'update' : 'create';
            $record ??= new StudyWeeklyTarget(['user_id' => $user->id, 'study_week_id' => $week->id]);
            $record->fill($values)->save();
            $record->revisions()->create(['actor_id' => auth()->id(), 'operation' => $operation, 'before' => $before, 'after' => $values]);

            return $record;
        });
    }
}
