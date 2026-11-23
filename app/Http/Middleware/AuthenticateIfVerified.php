<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the user of a public endpoint when there is one, through Sanctum (as `auth:sanctum` would),
 * without rejecting guests. A user whose email address is not verified is treated as a guest: the public
 * endpoints then answer as they would for someone logged out.
 */
class AuthenticateIfVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        $user = $request->user();
        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        $userResolver = Auth::userResolver();
        Auth::resolveUsersUsing(fn (): null => null);

        try {
            return $next($request);
        } finally {
            Auth::resolveUsersUsing($userResolver);
        }
    }
}
