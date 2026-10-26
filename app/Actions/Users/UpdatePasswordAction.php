<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdatePasswordAction
{
    use AsAction;

    public function handle(User $user, ?string $password = null): bool
    {
        if (! $password) {
            $password = Str::random(6);
        }

        $updated = $user->update([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ]);
        // Preserve the user's current authenticated password-change flow, revoke others.
        if ($updated && auth()->id() === $user->id && request()->hasSession()) {
            $fresh = $user->fresh();
            Auth::guard()->setUser($fresh);
            request()->session()->put('password_hash_'.Auth::getDefaultDriver(), Auth::guard()->hashPasswordForCookie($fresh->getAuthPassword()));
        }

        return $updated;
    }
}
