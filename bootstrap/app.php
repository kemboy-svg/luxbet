<?php

use App\Domain\DemoWallet\Exceptions\DemoWalletException;
use App\Domain\GameProviders\Exceptions\DemoSessionRefused;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenRequired;
use App\Http\Middleware\EnsureRealMoneyEnabled;
use App\Http\Middleware\EnsureStaffHasTwoFactor;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ThrottleRegistration;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            EnsureAccountIsActive::class,
            ThrottleRegistration::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Session-authenticated JSON API for the browser (same cookies as the
        // web app, CSRF-protected), without Inertia.
        $middleware->group('api.session', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            EnsureAccountIsActive::class,
        ]);

        $middleware->alias([
            'real-money' => EnsureRealMoneyEnabled::class,
            'staff.mfa' => EnsureStaffHasTwoFactor::class,
            'verified' => EnsureEmailIsVerifiedWhenRequired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Standard error envelope for the API. Never exposes stack traces,
        // SQL, secrets or provider details, even with APP_DEBUG on.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof DemoSessionRefused => ApiResponse::error(
                    'DEMO_SESSION_REFUSED', $e->getMessage(), 422,
                ),
                $e instanceof DemoWalletException => ApiResponse::error(
                    'DEMO_WALLET_REFUSED', $e->getMessage(), 422,
                ),
                $e instanceof ValidationException => ApiResponse::error(
                    'VALIDATION_ERROR', 'The request could not be processed.', 422, $e->errors(),
                ),
                $e instanceof AuthorizationException && $e->status() === 404 => ApiResponse::error(
                    'NOT_FOUND', 'The requested resource was not found.', 404,
                ),
                $e instanceof AuthenticationException => ApiResponse::error(
                    'UNAUTHENTICATED', 'Authentication is required.', 401,
                ),
                $e instanceof AuthorizationException, $e instanceof AccessDeniedHttpException => ApiResponse::error(
                    'FORBIDDEN', 'You are not allowed to perform this action.', 403,
                ),
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => ApiResponse::error(
                    'NOT_FOUND', 'The requested resource was not found.', 404,
                ),
                $e instanceof MethodNotAllowedHttpException => ApiResponse::error(
                    'METHOD_NOT_ALLOWED', 'This method is not allowed for this resource.', 405,
                ),
                $e instanceof ThrottleRequestsException => ApiResponse::error(
                    'RATE_LIMITED', 'Too many requests. Please try again later.', 429, [], $e->getHeaders(),
                ),
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    'HTTP_ERROR', 'The request could not be completed.', $e->getStatusCode(),
                ),
                default => ApiResponse::error(
                    'SERVER_ERROR', 'Something went wrong. Please try again later.', 500,
                ),
            };
        });
    })->create();
