<?php

namespace App\Actions\Study;

use App\Helpers\StudyTrackingSettings;
use App\Models\StudyNote;
use App\Models\StudyNoteRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveStudyNote
{
    use AsAction;

    public static function snapshot(StudyNote $note): array
    {
        return $note->only(['body', 'student_visible']);
    }

    public function handle(User $student, array $input, string $key, ?int $id = null, ?array $before = null, bool $remove = false): StudyNote
    {
        AuthorizeStudyPanel::run($student, true);
        abort_unless(StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn(), 403, __('study.disabled'));
        $input['body'] = trim($input['body'] ?? '');
        $values = Validator::make($input + ['key' => $key], [
            'body' => 'required|string|max:10000', 'student_visible' => 'required|boolean', 'key' => 'required|uuid',
        ])->validate();

        return DB::transaction(function () use ($student, $values, $id, $before, $remove) {
            StudyWrite::lock($student);
            AuthorizeStudyPanel::run($student, true);
            $actor = auth()->user();
            $note = $id ? StudyNote::where('user_id', $student->id)->whereKey($id)->lockForUpdate()->firstOrFail()
                : StudyNote::withTrashed()->where('author_id', $actor->id)->where('submission_key', $values['key'])->lockForUpdate()->first();
            $after = ['body' => $values['body'], 'student_visible' => (bool) $values['student_visible']];
            if (! $id && $note) {
                abort_unless(! $note->trashed() && $note->user_id === $student->id && self::snapshot($note) === $after, 409, __('study.conflict'));

                return $note;
            }
            if ($note) {
                abort_unless($actor->can('user-type:admin') || $note->author_id === $actor->id, 403);
                StudyWrite::unchanged($before, self::snapshot($note));
                if (! $remove && self::snapshot($note) === $after) {
                    return $note;
                }
            }
            abort_if($remove && ! $note, 422);
            $old = $note ? self::snapshot($note) : null;
            $operation = $remove ? 'delete' : ($note ? 'update' : 'create');
            $note ??= new StudyNote(['user_id' => $student->id, 'author_id' => $actor->id, 'submission_key' => $values['key']]);
            if ($remove) {
                $note->delete();
            } else {
                $note->fill($after)->save();
            }
            StudyNoteRevision::create(['note_id' => $note->id, 'actor_id' => $actor->id, 'operation' => $operation, 'before' => $old, 'after' => $remove ? null : $after]);

            return $note;
        });
    }
}
