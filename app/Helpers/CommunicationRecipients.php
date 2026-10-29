<?php

namespace App\Helpers;

use App\Actions\Communications\AuthorizeCommunication;
use App\Models\User;
use App\Models\WebPushSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CommunicationRecipients
{
    public static function parameters(Request $request): array
    {
        return ServerTable::parameters($request, ['name' => 'name', 'surname' => 'surname'], 'name', [
            'recipient_type' => 'nullable|in:user,teacher,admin',
            'push_permission' => 'nullable|in:yes,no',
        ]);
    }

    public static function query(array $parameters): Builder
    {
        AuthorizeCommunication::run();
        $query = User::active();
        if (! auth()->user()->can('user-type:admin')) {
            $query->whereIn('users.id', auth()->user()->students()->select('users.id'));
        }
        if (! empty($parameters['recipient_type'])) {
            $query->permission('user-type:'.$parameters['recipient_type']);
            if ($parameters['recipient_type'] === 'user') {
                $query->withoutPermission(['user-type:admin', 'user-type:teacher']);
            }
        }
        if (! empty($parameters['push_permission'])) {
            $subscribers = WebPushSubscription::query()->select('user_id')->where('app_id', WebPushSettings::appId())
                ->whereNotNull('consented_at')->whereNotNull('verified_at')->whereNull('revoked_at');
            $query->whereIn('users.id', $subscribers, not: $parameters['push_permission'] === 'no');
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('surname', 'like', $search)
                ->orWhere('username', 'like', $search)->orWhere('email', 'like', $search));
        }

        return $query->orderBy($parameters['sort'], $parameters['order'])->orderBy('id');
    }
}
