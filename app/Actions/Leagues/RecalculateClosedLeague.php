<?php

namespace App\Actions\Leagues;

use App\Models\League;
use Lorisleiva\Actions\Concerns\AsAction;

class RecalculateClosedLeague
{
    use AsAction;

    public function handle(int $leagueId): League
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('leagues:view') && $actor->can('leagues:run'), 403);
        $league = League::findOrFail($leagueId);
        abort_unless($league->is_live && $league->closed_at !== null, 409);

        return CalculateLeague::run('', '', leagueId: $league->id);
    }
}
