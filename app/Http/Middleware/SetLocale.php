<?php

namespace App\Http\Middleware;

use App\Enums\InterfaceLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the language of the response (validation messages, errors): the logged-in user's choice, otherwise the
 * one the SPA sends in the `X-Locale` header (a visitor's device), otherwise the application's default.
 */
class SetLocale
{
    public const HEADER = 'X-Locale';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? InterfaceLocale::tryFrom((string) $request->header(self::HEADER));

        if ($locale !== null) {
            App::setLocale($locale->value);
        }

        return $next($request);
    }
}
