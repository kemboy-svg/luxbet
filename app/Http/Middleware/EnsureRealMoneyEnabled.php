<?php

namespace App\Http\Middleware;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards any route that would touch real money. Registered as the
 * `real-money` middleware alias. In this release no such route exists,
 * and this middleware denies every request it sees.
 */
class EnsureRealMoneyEnabled
{
    public function __construct(private readonly LaunchGate $gate) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->gate->isRealMoneyEnabled()) {
            return $next($request);
        }

        Log::warning('Blocked request to a real-money route while the launch gate is closed.', [
            'route' => $request->route()?->getName() ?? $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::error(
                code: 'REAL_MONEY_DISABLED',
                message: 'Real-money features are not available. LuxBet is a demo-only platform.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        abort(Response::HTTP_FORBIDDEN, 'Real-money features are not available. LuxBet is a demo-only platform.');
    }
}
