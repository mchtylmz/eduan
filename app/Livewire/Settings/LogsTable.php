<?php

namespace App\Livewire\Settings;

use App\Enums\RoleTypeEnum;
use App\Helpers\TableCache;
use App\Models\Log;
use App\Models\User;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class LogsTable extends Component
{
    use ServerTableActions;

    protected string $resource = 'settings';

    protected string $tableName = 'logs';

    protected function builder(): Builder
    {
        return Log::with('user');
    }

    public function render()
    {
        $this->selectedRecord();
        $students = auth()->user()->can(RoleTypeEnum::TEACHER->value) ? auth()->user()->students()->pluck('users.id')->all() : null;

        return view('livewire.backend.settings.logs-server-table', [
            'users' => TableCache::options('log-users-'.hash('sha256', json_encode($students)),
                fn () => User::orderByDesc('id')->when($students !== null, fn ($query) => $query->whereIn('id', $students))
                    ->get()->mapWithKeys(fn ($user) => [$user->id => '('.$user->email.') '.$user->display_name])->all()),
            'types' => data()->filters()->logTypes(),
        ]);
    }
}
