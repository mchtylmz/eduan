<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Settings\SettingSaveAction;
use App\Enums\SettingsTabsEnum;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $activeTab = SettingsTabsEnum::tryFrom(request('activeTab')) ?? SettingsTabsEnum::GENERAL;
        $canStudySettings = auth()->user()?->can('user-type:admin');
        abort_if(in_array($activeTab, [SettingsTabsEnum::STUDY, SettingsTabsEnum::NOTIFICATIONS], true) && ! $canStudySettings, 403);

        return view('backend.settings.index', [
            'title' => __('Site Ayarları'),
            'activeTab' => $activeTab,
            'tabs' => array_filter(SettingsTabsEnum::cases(), fn ($tab) => ! in_array($tab, [SettingsTabsEnum::STUDY, SettingsTabsEnum::NOTIFICATIONS], true) || $canStudySettings),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->cannot('settings:update')) {
            abort(403, __('Ayarlar güncellenemez, yetkiniz bulunmuyor!'));
        }

        SettingSaveAction::run($request);

        return response()->json([
            'message' => __('Ayarlar başarıyla kayıt edildi'),
            'refresh' => true,
        ]);
    }

    public function getLogsData(Request $request)
    {
        abort_unless($request->user()?->can('dashboard:access') && $request->user()->can('settings:view'), 403);
        $sorts = ['id' => 'logs.id', 'name' => User::select('name')->whereColumn('users.id', 'logs.user_id'),
            'surname' => User::select('surname')->whereColumn('users.id', 'logs.user_id'),
            'log_type' => 'logs.log_type', 'agent' => 'logs.agent', 'browser' => 'logs.browser',
            'ip' => 'logs.ip', 'log_date' => 'logs.log_date'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', [
            'users' => ['sometimes', 'array', 'max:100'], 'users.*' => ['integer', 'distinct', 'exists:users,id'],
            'log_type' => ['nullable', Rule::in(array_keys(data()->filters()->logTypes()))],
        ]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = Log::query()->with('user');
        if (! empty($parameters['users'])) {
            $query->whereIn('user_id', $parameters['users']);
        }
        if (! empty($parameters['log_type'])) {
            $query->where('log_type', $parameters['log_type']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(function ($query) use ($search) {
                $query->whereHas('user', fn ($user) => $user->where('name', 'like', $search)->orWhere('surname', 'like', $search));
                foreach (['log_type', 'agent', 'browser', 'ip', 'log_date'] as $field) {
                    $query->orWhere($field, 'like', $search);
                }
            });
        }
        $data = ServerTable::page('logs', $query, $parameters, $sorts, fn (Log $log) => [
            'id' => $log->id, 'name' => $log->user?->name ?? '-', 'surname' => $log->user?->surname ?? '-',
            'log_type' => data()->filters()->logTypes($log->log_type), 'table_name' => $log->table_name,
            'agent' => $log->agent, 'browser' => $log->browser, 'ip' => $log->ip,
            'log_date' => dateFormat($log->log_date, 'd/m/Y H:i'), 'can_delete' => false, 'edit_url' => null,
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function logs()
    {
        return view('backend.settings.logs', [
            'title' => __('Loglar'),
        ]);
    }
}
