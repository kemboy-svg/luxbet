<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of any signed-in user whose account is no longer active
 * (suspended, closed or pending), on their very next request.
 */
class EnsureAccountIsActive
{
    public const MESSAGE = 'This account is not active. Please contact support.';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->isActive()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::error('ACCOUNT_INACTIVE', self::MESSAGE, Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
    }
}
