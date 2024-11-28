<?php

namespace App\Http\Controllers\Backend;

use App\Enums\RoleTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    protected array $userTabs = [
        'user',
        'password',
        'favorite',
        'tests',
        'mailing'
    ];

    public function index()
    {
        return view('backend.users.index', [
            'title' => __('Yöneticiler'),
            'type' => RoleTypeEnum::ADMIN->value
        ]);
    }

    public function create()
    {
        if (!request()->user()->canany(['users:add'])) {
            abort(403, __('Kullanıcı eklenemez, yetkiniz bulunmuyor!'));
        }

        return view('backend.users.create', [
            'title' => __('Kullanıcı Ekle')
        ]);
    }

    public function edit(User $user)
    {
        if (!request()->user()->canany(['users:view'])) {
            abort(403, __('Kullanıcı güncellenemez, yetkiniz bulunmuyor!'));
        }

        $activeTab = in_array(request()->input('tab'), $this->userTabs) ? request()->input('tab') : 'user';

        return view('backend.users.edit', [
            'title' => __('Kullanıcı Düzenle'),
            'user' => $user,
            'activeTab' => $activeTab
        ]);
    }
}
