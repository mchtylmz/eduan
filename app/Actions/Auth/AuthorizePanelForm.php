<?php

namespace App\Actions\Auth;

use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizePanelForm
{
    use AsAction;

    public function handle(string $resource, string $operation): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can($resource.':view') && $actor->can($resource.':'.$operation), 403);
    }
}
