<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Notifications\RegisterWebSubscription;
use App\Actions\Notifications\RevokeWebSubscription;
use App\Enums\PageMenuEnum;
use App\Enums\StatusEnum;
use App\Exceptions\OneSignalFailure;
use App\Helpers\WebPushSettings;
use App\Http\Controllers\Controller;
use App\Models\CommunicationDelivery;
use App\Models\Page;
use App\Models\WebPushSubscription;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(WebPushSettings::enabled(), 404);

        $title = __('notifications.title');
        $explanation = Page::where('menu', PageMenuEnum::NOTIFICATIONS)->where('status', StatusEnum::ACTIVE)->orderBy('id')->first();
        $devices = WebPushSubscription::where('user_id', $request->user()->id)
            ->where(fn ($query) => $query->whereNull('revoked_at')->orWhereNull('provider_revoked_at'))
            ->orderByDesc('verified_at')->get();
        $messages = CommunicationDelivery::where('user_id', $request->user()->id)->where('channel', 'push')->where('status', 'sent')->orderByDesc('id')->limit(10)->get();
        $ready = WebPushSettings::ready();

        return response()->view('frontend.notifications.index', compact('title', 'explanation', 'devices', 'messages', 'ready'))->header('Cache-Control', 'private, no-store');
    }

    public function context(Request $request)
    {
        abort_unless(WebPushSettings::ready(), 503, __('notifications.not_configured'));
        abort_unless($request->getSchemeAndHttpHost() === WebPushSettings::origin(), 503, __('notifications.wrong_origin'));

        return response()->json(['config' => WebPushSettings::publicConfig(), 'externalId' => WebPushSettings::externalId($request->user())])->header('Cache-Control', 'private, no-store');
    }

    public function register(Request $request)
    {
        try {
            $record = RegisterWebSubscription::run($request->user(), $request->all());
            $request->session()->put('webPushSubscriptionRecordId', $record->id);

            return response()->json(['id' => $record->id])->header('Cache-Control', 'private, no-store');
        } catch (OneSignalFailure $exception) {
            return response()->json(['message' => __('notifications.service_error')], 503);
        }
    }

    public function revoke(Request $request, int $id)
    {
        try {
            RevokeWebSubscription::run($id);
        } catch (OneSignalFailure $exception) {
            return back()->with('notificationMessage', __('notifications.revoke_pending'));
        }

        return back()->with('notificationMessage', __('notifications.revoked'));
    }
}
