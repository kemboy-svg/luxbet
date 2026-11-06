<?php

use App\Domain\GameProviders\Adapters\LocalSimulatorAdapter;

return [

    /*
    |--------------------------------------------------------------------------
    | Environment mode
    |--------------------------------------------------------------------------
    |
    | Labels the deployment as demo, staging or production. It is used for
    | display and for safety checks (for example, refusing production provider
    | endpoints in demo mode). It never enables real-money features on its own.
    |
    */

    'environment_mode' => env('LUXBET_ENVIRONMENT_MODE', 'demo'),

    /*
    |--------------------------------------------------------------------------
    | Real-money launch gate
    |--------------------------------------------------------------------------
    |
    | Real-money gambling, deposits, withdrawals, wagering and cash-equivalent
    | rewards are permanently disabled in this release.
    |
    | This value is intentionally a literal `false` and NOT read from the
    | environment: there must be no environment-variable bypass. Changing it
    | requires a separately approved project change after documented legal,
    | regulatory, provider and payment approvals, plus a multi-person approval
    | workflow that does not exist yet. See docs/regulatory-launch-gate.md.
    |
    */

    'real_money' => [
        'enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security headers
    |--------------------------------------------------------------------------
    |
    | Applied by App\Http\Middleware\SetSecurityHeaders. HSTS is only sent
    | over HTTPS. Set LUXBET_CSP_REPORT_ONLY=true to trial a policy change
    | without blocking anything.
    |
    */

    'security' => [
        'hsts_max_age' => (int) env('LUXBET_HSTS_MAX_AGE', 31536000),
        'csp' => [
            'enabled' => (bool) env('LUXBET_CSP_ENABLED', true),
            'report_only' => (bool) env('LUXBET_CSP_REPORT_ONLY', false),
            'upgrade_insecure_requests' => (bool) env('LUXBET_CSP_UPGRADE_INSECURE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email verification
    |--------------------------------------------------------------------------
    |
    | When false (the current default), players can use the site straight
    | after registering and no verification email is sent. The verification
    | pages stay in place; set LUXBET_REQUIRE_EMAIL_VERIFICATION=true to turn
    | the requirement back on (a working mailer is then needed).
    |
    */

    'require_email_verification' => (bool) env('LUXBET_REQUIRE_EMAIL_VERIFICATION', false),

    /*
    |--------------------------------------------------------------------------
    | Demo wallet
    |--------------------------------------------------------------------------
    |
    | Demo credits are abstract units with no monetary value. Wallets use the
    | ISO 4217 "no currency" code XXX, with no minor unit, so one stored unit
    | is one demo credit. Credits can never be bought, transferred,
    | withdrawn, redeemed or converted.
    |
    */

    'demo_wallet' => [
        'currency' => 'XXX',
        'initial_allowance' => 10_000,
        // Upper bound for a single ledger entry, as a guard against bad input.
        'max_transaction' => 1_000_000,
        'max_admin_adjustment' => 100_000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Game providers
    |--------------------------------------------------------------------------
    |
    | One adapter class per provider slug. Only the local simulator exists
    | until a real provider's official sandbox documentation is supplied.
    | Launch URLs must be HTTPS (or this app's own URL) on an allowlisted
    | host; anything else is refused.
    |
    */

    'providers' => [
        'adapters' => [
            'local-simulator' => LocalSimulatorAdapter::class,
        ],

        'session_minutes' => 30,

        // Extra hosts allowed for provider launch URLs (comma-separated).
        // This app's own host is always allowed, for the simulator.
        'launch_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('LUXBET_PROVIDER_LAUNCH_HOSTS', ''))))),

        'webhook' => [
            'max_body_bytes' => 65_536,
            'replay_window_seconds' => 300,
        ],

        'http' => [
            'timeout_seconds' => 5,
            'connect_timeout_seconds' => 3,
        ],

        'simulator' => [
            // Set to false to simulate provider downtime.
            'available' => (bool) env('LUXBET_SIMULATOR_AVAILABLE', true),
            'stakes' => [10, 50, 100, 250, 500],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Terms of demo use
    |--------------------------------------------------------------------------
    |
    | Version recorded against each player when they accept the terms at
    | registration. Bump it when the terms change.
    |
    */

    'terms_version' => '2026-11-06',

    /*
    |--------------------------------------------------------------------------
    | Demo-mode notice
    |--------------------------------------------------------------------------
    |
    | Shown on every page. Server-rendered so it cannot be hidden by
    | frontend state.
    |
    */

    'demo_notice' => [
        'title' => 'DEMO MODE — NO REAL-MONEY GAMBLING',
        'body' => 'Credits are fictional and have no cash value.',
    ],

];
