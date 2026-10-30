<?php

namespace App\Http\Controllers\Backend;

use App\Enums\RoleTypeEnum;
use App\Enums\YesNoEnum;
use App\Helpers\ServerTable;
use App\Helpers\UserRelatedTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function getFavoritesData(Request $request)
    {
        return UserRelatedTable::favorites($request);
    }

    public function getStudentsData(Request $request)
    {
        return UserRelatedTable::assignments($request, true);
    }

    public function getTeachersData(Request $request)
    {
        return UserRelatedTable::assignments($request, false);
    }

    public function getUnsolvedTopicsData(Request $request)
    {
        return UserRelatedTable::unsolvedTopics($request);
    }

    public function getStatsData(Request $request)
    {
        return (new StatsController)->getData($request);
    }

    public function getStatsExport(Request $request)
    {
        return (new StatsController)->getExport($request);
    }

    public function getData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('users:view'), 403);
        $sorts = ['username' => 'users.username', 'name' => 'users.name', 'surname' => 'users.surname',
            'email_verified' => 'users.email_verified', 'status' => 'users.status', 'last_login_at' => 'users.last_login_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'username', [
            'student_group' => ['nullable', 'in:normal,premium,all'],
            'registered_since' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'roles' => ['sometimes', 'array', 'max:20'],
            'roles.*' => ['string', 'max:100', 'distinct', 'exists:roles,name'],
            'email_verified' => ['sometimes', 'array', 'max:2'],
            'email_verified.*' => ['integer', 'in:0,1', 'distinct'],
            'last_login_from' => ['nullable', 'date_format:Y-m-d'],
            'last_login_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('last_login_from') ? ['after_or_equal:last_login_from'] : [])],
            'registered_from' => ['nullable', 'date_format:Y-m-d'],
            'registered_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('registered_from') ? ['after_or_equal:registered_from'] : [])],
        ]);
        $query = User::query()->with('roles');
        $students = [];
        if ($actor->can(RoleTypeEnum::TEACHER->value)) {
            $students = $actor->students()->orderBy('users.id')->pluck('users.id')->all();
            $query->whereIn('users.id', $students);
        }
        if (! empty($parameters['student_group'])) {
            match ($parameters['student_group']) {
                'normal' => $query->user(),
                'premium' => $query->premiumUser(),
                'all' => $query->permission(RoleTypeEnum::USER->value),
            };
        }
        if (! empty($parameters['registered_since'])) {
            $query->where('users.created_at', '>=', Carbon::createFromFormat('Y-m-d\TH:i:s', $parameters['registered_since']));
        }
        if (! empty($parameters['roles'])) {
            $query->role($parameters['roles']);
        }
        if (! empty($parameters['email_verified'])) {
            $query->whereIn('users.email_verified', $parameters['email_verified']);
        }
        if (! empty($parameters['status'])) {
            $query->where('users.status', $parameters['status']);
        }
        foreach (['last_login' => 'last_login_at', 'registered' => 'created_at'] as $filter => $column) {
            if (! empty($parameters[$filter.'_from'])) {
                $query->where('users.'.$column, '>=', $parameters[$filter.'_from'].' 00:00:00');
            }
            if (! empty($parameters[$filter.'_to'])) {
                $query->where('users.'.$column, '<', Carbon::parse($parameters[$filter.'_to'])->addDay()->startOfDay());
            }
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('users.username', 'like', $search)
                ->orWhere('users.name', 'like', $search)->orWhere('users.surname', 'like', $search)
                ->orWhere('users.status', 'like', $search));
        }
        $data = ServerTable::page('users', $query, $parameters, $sorts, fn (User $user) => [
            'id' => $user->id, 'username' => $user->username, 'name' => $user->name, 'surname' => $user->surname,
            'roles' => $user->roles->pluck('name')->all(),
            'email_verified' => ['label' => YesNoEnum::YES->is($user->email_verified) ? __('Onaylı') : __('Onaylı Değil'),
                'class' => YesNoEnum::YES->is($user->email_verified) ? 'success' : 'danger'],
            'status' => ['label' => $user->status->name(), 'class' => $user->status->class()],
            'last_login_at' => $user->last_login_at ? dateFormat($user->last_login_at) : '-',
            'edit_url' => $actor->can('users:update') ? route('admin.users.edit', $user) : null,
            'can_delete' => $actor->can('users:delete') && $actor->id !== $user->id,
        ], $students);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    protected array $userTabs = [
        'user',
        'password',
        'teachers',
        'favorite',
        'tests',
        'exams',
        'mailing',
        'topicsNotResult',
        'topicsStats',
    ];

    public function index()
    {
        return view('backend.users.index', [
            'title' => __('Kullanıcılar'),
            'type' => RoleTypeEnum::ADMIN->value,
        ]);
    }

    public function create()
    {
        if (! request()->user()->canany(['users:add'])) {
            abort(403, __('Kullanıcı eklenemez, yetkiniz bulunmuyor!'));
        }

        return view('backend.users.create', [
            'title' => __('Kullanıcı Ekle'),
        ]);
    }

    public function edit(User $user)
    {
        if (! request()->user()->canany(['users:view'])) {
            abort(403, __('Kullanıcı güncellenemez, yetkiniz bulunmuyor!'));
        }

        $activeTab = in_array(request()->input('tab'), $this->userTabs) ? request()->input('tab') : 'user';
        $ownAssignments = auth()->id() === $user->id && $activeTab === 'teachers';

        if (auth()->user()->can(RoleTypeEnum::TEACHER->value) && ! $ownAssignments && ! auth()->user()->students()->where('users.id', $user->id)->exists()) {
            return redirect()->route('admin.users.index')->with([
                'status' => 'error',
                'message' => __('Kullanıcı güncellenemez, yetkiniz bulunmuyor!'),
            ]);
        }

        return view('backend.users.edit', [
            'title' => __('Kullanıcı Düzenle'),
            'user' => $user,
            'activeTab' => $activeTab,
        ]);
    }
}
