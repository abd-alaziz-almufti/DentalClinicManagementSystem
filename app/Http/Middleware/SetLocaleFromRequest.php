<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language');

        // Extract first 2 characters if header is in format like 'ar-EG,ar;q=0.9'
        if ($locale) {
            $locale = strtolower(substr($locale, 0, 2));
        }

        if (in_array($locale, ['ar', 'en'], true)) {
            app()->setLocale($locale);
        } else {
            app()->setLocale('en'); // Default fallback
        }

        return $next($request);
    }
}
