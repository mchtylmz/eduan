<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Roles\CreateOrUpdateAction;
use App\Enums\RoleTypeEnum;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Roles\CreateRoleRequest;
use App\Http\Requests\Backend\Roles\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function getData(Request $request)
    {
        abort_unless($request->user()?->can('dashboard:access') && $request->user()->can('roles:view'), 403);
        $sorts = ['name' => 'roles.name', 'users_count' => 'users_count'];
        $parameters = ServerTable::parameters($request, $sorts, 'name');
        $query = Role::query()->with('permissions')->withCount('users');
        if ($parameters['search'] !== '') {
            $query->where('name', 'like', '%'.$parameters['search'].'%');
        }
        $data = ServerTable::page('roles', $query, $parameters, $sorts, function (Role $role) use ($request) {
            $group = collect(RoleTypeEnum::cases())->first(fn ($type) => $role->permissions->contains('name', $type->value));

            return ['id' => $role->id, 'name' => $role->name, 'group' => $group?->name() ?? '-',
                'users_count' => $role->users_count,
                'edit_url' => $request->user()->can('roles:update') ? route('admin.roles.edit', $role) : null,
                'can_delete' => $request->user()->can('roles:delete')];
        });

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.roles.index', [
            'title' => __('Yetkiler'),
        ]);
    }

    public function create()
    {
        if (request()->user()->cannot('roles:add')) {
            abort(403, __('Yetki eklenemez, yetkiniz bulunmuyor!'));
        }

        return view('backend.roles.create', [
            'title' => __('Yetki Ekle'),
        ]);
    }

    public function store(CreateRoleRequest $request)
    {
        CreateOrUpdateAction::run(
            name: $request->validated('name'),
            permissions: $request->validated('permissions'),
        );

        return response()->json([
            'message' => __('Yetki başarıyla eklendi'),
            'redirect' => route('admin.roles.index'),
        ]);
    }

    public function edit(Role $role)
    {
        if (request()->user()->cannot('roles:update')) {
            abort(403, __('Yetki güncellenemez, yetkiniz bulunmuyor!'));
        }

        return view('backend.roles.edit', [
            'title' => __('Yetki Düzenle'),
            'role' => $role,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        CreateOrUpdateAction::run(
            name: $request->validated('name'),
            permissions: $request->validated('permissions'),
            role: $role
        );

        return response()->json([
            'message' => __('Yetki başarıyla güncellendi'),
            'redirect' => route('admin.roles.index'),
        ]);
    }
}
