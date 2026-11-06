<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces the `verified` middleware. Enforces email verification only when
 * luxbet.require_email_verification is on; otherwise lets the request through.
 */
class EnsureEmailIsVerifiedWhenRequired extends EnsureEmailIsVerified
{
    /**
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @param  string|null  $redirectToRoute
     */
    public function handle($request, Closure $next, $redirectToRoute = null): mixed
    {
        if (! config('luxbet.require_email_verification')) {
            // The parent's docblock narrows the return type more than the
            // pipeline allows; any Response from $next is valid here.
            return $next($request); // @phpstan-ignore return.type
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
