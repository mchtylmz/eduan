<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('lang') && $language = data()->language($request->input('lang'))) {
            $locale = $language->code;
            session()->put('locale', $locale);
        }
        elseif ($request->session()->has('locale')) {
            $locale = $request->session()->get('locale');
        }
        else {
            $locale = settings()->defaultLocale ?? 'tr';
            session()->put('locale', $locale);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
