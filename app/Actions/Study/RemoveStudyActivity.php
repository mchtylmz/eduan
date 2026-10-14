<?php

namespace App\Actions\Study;

use App\Models\StudyActivityEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveStudyActivity
{
    use AsAction;

    public function handle(User $user, int $entryId, array $expected): void
    {
        AuthorizeStudyAccess::run($user, write: true);
        DB::transaction(function () use ($user, $entryId, $expected) {
            StudyWrite::lock($user);
            $record = StudyActivityEntry::withTrashed()->where('user_id', $user->id)->whereKey($entryId)->lockForUpdate()->firstOrFail();
            AuthorizeStudyChange::run($user, 'activity', $record->activity_date->toDateString());
            if ($record->trashed()) {
                return;
            }
            $before = StudyWrite::snapshot($record);
            StudyWrite::unchanged($expected, $before);
            $record->delete();
            $record->revisions()->create(['actor_id' => auth()->id(), 'operation' => 'delete', 'before' => $before, 'after' => null]);
        });
    }
}
