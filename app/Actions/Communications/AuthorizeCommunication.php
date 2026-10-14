<?php

namespace App\Actions\Communications;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeCommunication
{
    use AsAction;

    public function handle(?User $recipient = null, bool $write = false): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('users:view') && $actor->can('communications:view')
            && (! $write || $actor->can('communications:send')) && $actor->canany(['user-type:admin', 'user-type:teacher']), 403);
        if ($recipient && ! $actor->can('user-type:admin')) {
            abort_unless($actor->students()->where('users.id', $recipient->id)->exists(), 403);
        }
    }
}
