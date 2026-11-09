<?php

namespace App\Http\Middleware;

use App\Actions\Auth\ImpersonateUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserImpersonation
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->has(ImpersonateUser::SESSION_KEY)) {
            return $next($request);
        }
        if (! $request->routeIs('frontend.impersonation.return')) {
            $actor = ImpersonateUser::original();
            $target = $request->user();
            if (! $actor || ! $target || session(ImpersonateUser::SESSION_KEY.'.target_id') !== $target->id || ! ImpersonateUser::permitted($actor, $target)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(403);
            }
            // Panel routes still apply the target account's own permissions and student scope.
            abort_if($request->routeIs('frontend.notifications.context', 'frontend.notifications.register', 'frontend.notifications.revoke'), 403);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
