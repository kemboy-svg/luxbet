<?php

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
