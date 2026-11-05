<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff must have confirmed two-factor authentication before using any
 * admin route. Without it they are sent to security settings to set it up.
 */
class EnsureStaffHasTwoFactor
{
    public const MESSAGE = 'Set up two-factor authentication to use the admin area.';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasConfirmedTwoFactor()) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::error('MFA_REQUIRED', self::MESSAGE, Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('security.edit')->with('status', self::MESSAGE);
    }
}
