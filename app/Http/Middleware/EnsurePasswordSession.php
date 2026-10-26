<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\AuthenticateSession;

class EnsurePasswordSession extends AuthenticateSession
{
    public function handle($request, \Closure $next)
    {
        // Existing sessions without a password baseline cannot prove they predate a reset.
        // Remembered logins are checked against their cookie hash by the parent middleware.
        if ($request->hasSession() && $request->user()?->getAuthPassword()
            && ! $this->guard()->viaRemember()
            && ! $request->session()->has('password_hash_'.$this->auth->getDefaultDriver())) {
            $this->logout($request);
        }

        return parent::handle($request, $next);
    }
}
