<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Roles\CreateOrUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Roles\CreateRoleRequest;
use App\Http\Requests\Backend\Roles\UpdateRoleRequest;
use App\Models\Role;

class RolesController extends Controller
{
    public function index()
    {
        return view('backend.roles.index', [
            'title' => __('Yetkiler')
        ]);
    }

    public function create()
    {
        if (request()->user()->cannot('roles:add')) {
            abort(403, __('Yetki eklenemez, yetkiniz bulunmuyor!'));
        }

        return view('backend.roles.create', [
            'title' => __('Yetki Ekle')
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
            'redirect' => route('admin.roles.index')
        ]);
    }


    public function edit(Role $role)
    {
        if (request()->user()->cannot('roles:update')) {
            abort(403, __('Yetki güncellenemez, yetkiniz bulunmuyor!'));
        }

        return view('backend.roles.edit', [
            'title' => __('Yetki Düzenle'),
            'role' => $role
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
            'redirect' => route('admin.roles.index')
        ]);
    }
}
