<?php

namespace App\Actions\Study;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeStudyPanel
{
    use AsAction;

    public function handle(?User $student = null, bool $write = false): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('users:view') && $actor->can('study:access')
            && $actor->can($write ? 'study:update' : 'study:view')
            && $actor->canany(['user-type:admin', 'user-type:teacher']), 403);
        if ($student) {
            abort_unless($student->can('user-type:user') && ! $student->canany(['user-type:admin', 'user-type:teacher']), 403);
            if (! $actor->can('user-type:admin')) {
                abort_unless($actor->students()->where('users.id', $student->id)->exists(), 403);
            }
        }
    }
}
