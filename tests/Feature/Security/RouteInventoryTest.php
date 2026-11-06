<?php

namespace Tests\Feature\Security;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Guards the route table itself, so a new route cannot quietly skip the
 * admin protections or introduce a real-money endpoint.
 * See docs/access-control-review.md.
 */
class RouteInventoryTest extends TestCase
{
    /**
     * Admin routes whose permission is checked by a FormRequest or policy
     * instead of a `can:` route middleware.
     */
    private const ADMIN_AUTHORIZED_IN_CODE = [
        'admin.dashboard',
        'admin.players.status.update',
        'admin.players.demo-wallet.adjustments.store',
        'admin.games.update',
        'admin.providers.status.update',
    ];

    /**
     * State-changing routes that are intentionally reachable without a
     * session (sign-in flows and signed provider webhooks).
     */
    private const PUBLIC_WRITE_PREFIXES = [
        'login', 'logout', 'register', 'forgot-password', 'reset-password', 'two-factor-challenge',
        'email/verification-notification', 'user/confirm-password', 'passkeys', 'api/v1/webhooks/providers/',
    ];

    /**
     * @return list<Route>
     */
    private function routes(): array
    {
        return array_values(Router::getRoutes()->getRoutes());
    }

    public function test_every_admin_route_has_the_full_staff_stack(): void
    {
        $admin = array_filter($this->routes(), fn (Route $route): bool => str_starts_with($route->uri(), 'admin'));
        $this->assertNotEmpty($admin);

        foreach ($admin as $route) {
            $middleware = $route->gatherMiddleware();

            foreach (['auth', 'verified', 'can:admin.access', 'staff.mfa'] as $required) {
                $this->assertContains($required, $middleware, "{$route->uri()} is missing {$required}");
            }

            $hasOwnPermission = collect($middleware)
                ->contains(fn (mixed $m): bool => is_string($m) && str_starts_with($m, 'can:') && $m !== 'can:admin.access');

            $this->assertTrue(
                $hasOwnPermission || in_array($route->getName(), self::ADMIN_AUTHORIZED_IN_CODE, true),
                "{$route->uri()} needs its own permission check (or must be listed as authorised in code)",
            );
        }
    }

    public function test_state_changing_routes_require_a_session_or_a_signature(): void
    {
        $unprotected = [];

        foreach ($this->routes() as $route) {
            $methods = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']);
            if ($methods === []) {
                continue;
            }

            $uri = $route->uri();
            $isPublic = collect(self::PUBLIC_WRITE_PREFIXES)->contains(fn (string $prefix): bool => str_starts_with($uri, $prefix));

            if ($isPublic) {
                continue;
            }

            // `auth` or a guard-qualified form such as Fortify's `auth:web`.
            $requiresSignIn = collect($route->gatherMiddleware())
                ->contains(fn (mixed $m): bool => $m === 'auth' || (is_string($m) && str_starts_with($m, 'auth:')));

            if (! $requiresSignIn) {
                $unprotected[] = implode('|', $methods).' '.$uri;
            }
        }

        $this->assertSame([], $unprotected, 'These routes change state without requiring sign-in');
    }

    public function test_no_real_money_routes_exist(): void
    {
        foreach ($this->routes() as $route) {
            $this->assertDoesNotMatchRegularExpression(
                '/deposit|withdraw|cash-?out|payout|payment|checkout|cashier|top-?up|purchase/i',
                $route->uri().' '.($route->getName() ?? ''),
                "Route {$route->uri()} looks like a real-money endpoint",
            );
        }
    }

    public function test_staff_management_requires_password_confirmation(): void
    {
        foreach ($this->routes() as $route) {
            if (str_starts_with((string) $route->getName(), 'admin.staff.')) {
                $this->assertContains('password.confirm', $route->gatherMiddleware(), $route->uri());
                $this->assertContains('can:staff.manage', $route->gatherMiddleware(), $route->uri());
            }
        }
    }
}
