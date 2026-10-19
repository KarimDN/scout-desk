<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('scoutdesk.locales');
        $locale = $request->session()->get('locale');

        // Only whitelisted locales are ever applied. Anything else (for
        // example a tampered session value) falls back to the default.
        // From Task 2.8, a logged-in user's saved language takes precedence.
        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = config('scoutdesk.default_locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}