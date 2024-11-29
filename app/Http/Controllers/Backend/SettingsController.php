<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Settings\SettingSaveAction;
use App\Enums\SettingsTabsEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $activeTab = SettingsTabsEnum::tryFrom(request('activeTab')) ?? SettingsTabsEnum::GENERAL;

        return view('backend.settings.index', [
            'title' => __('Site Ayarları'),
            'activeTab' => $activeTab,
            'tabs' => SettingsTabsEnum::cases(),
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
            'refresh' => true
        ]);
    }

    public function logs()
    {
        return view('backend.settings.logs', [
            'title' => __('Loglar')
        ]);
    }
}
