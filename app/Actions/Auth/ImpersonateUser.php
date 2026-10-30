<?php

namespace App\Actions\Auth;

use App\Enums\StatusEnum;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class ImpersonateUser
{
    use AsAction, Loggable;

    public const SESSION_KEY = 'account_impersonation';

    public static function permitted(User $actor, User $target): bool
    {
        return $actor->status === StatusEnum::ACTIVE && $target->status === StatusEnum::ACTIVE
            && $actor->id !== $target->id
            && $actor->can(['dashboard:access', 'users:view', 'users:impersonate'])
            && ! $target->can('user-type:admin') && $target->canany(['user-type:user', 'user-type:teacher'])
            && (! $actor->can('user-type:teacher') || $actor->students()->where('users.id', $target->id)->exists());
    }

    public static function original(): ?User
    {
        $state = session(self::SESSION_KEY);
        if (! is_array($state) || ! is_int($state['actor_id'] ?? null) || ! is_string($state['proof'] ?? null)) {
            return null;
        }
        $actor = User::find($state['actor_id']);

        return $actor && $actor->status === StatusEnum::ACTIVE
            && $actor->can(['dashboard:access', 'users:view', 'users:impersonate'])
            && hash_equals($state['proof'], self::proof($actor)) ? $actor : null;
    }

    private static function proof(User $actor): string
    {
        return hash_hmac('sha256', $actor->getAuthPassword(), config('app.key'));
    }

    private static function login(User $user): void
    {
        Auth::login($user);
        // A remembered administrator must not replace the temporary identity on a later request.
        Cookie::queue(Cookie::forget(Auth::guard()->getRecallerName()));
        request()->session()->regenerate(true);
        request()->session()->regenerateToken();
        session()->put('password_hash_'.Auth::getDefaultDriver(), Auth::guard()->hashPasswordForCookie($user->getAuthPassword()));
    }

    public function handle(User $target): void
    {
        $actor = auth()->user();
        abort_if(session()->has(self::SESSION_KEY), 403);
        abort_unless($actor && self::permitted($actor, $target), 403);
        $this->customLog('users', 'impersonation_started', ['id' => $target->id, 'actor_id' => $actor->id, 'target_id' => $target->id]);
        session()->put(self::SESSION_KEY, ['actor_id' => $actor->id, 'target_id' => $target->id,
            'proof' => self::proof($actor), 'token' => Str::random(64),
            'push_record_id' => session()->pull('webPushSubscriptionRecordId')]);
        self::login($target);
    }

    public function restore(string $token): void
    {
        $state = session(self::SESSION_KEY);
        abort_unless(is_array($state) && is_string($state['token'] ?? null) && $token !== '' && hash_equals($state['token'], $token), 403);
        $actor = self::original();
        if (! $actor) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            abort(403);
        }
        $this->customLog('users', 'impersonation_finished', ['id' => $state['target_id'], 'actor_id' => $actor->id, 'target_id' => $state['target_id']]);
        session()->forget(self::SESSION_KEY);
        if ($state['push_record_id'] ?? null) {
            session()->put('webPushSubscriptionRecordId', $state['push_record_id']);
        }
        self::login($actor);
    }
}
