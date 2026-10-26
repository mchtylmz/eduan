<?php

namespace App\Actions\Study;

use App\Actions\Communications\AuthorizeCommunication;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeStudyCommunication
{
    use AsAction;

    public function handle(?User $student = null): void
    {
        AuthorizeStudyPanel::run($student);
        abort_unless(auth()->user()->can('study:communicate'), 403);
        AuthorizeCommunication::run($student, write: true);
    }
}
