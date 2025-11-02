<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Detect locale from request header or parameter (adjust as needed)
        $locale = $request->header('Accept-Language') ?? $request->query('locale') ?? env("APP_LOCALE");    

        // Optionally validate the locale is supported
        $availableLocales = ['en', 'ar']; // Add your supported locales here

        if (!in_array($locale, $availableLocales)) {
            $locale = 'en'; // fallback locale
        }

        // Set the application locale
        App::setLocale($locale);

        return $next($request);
    }
}
