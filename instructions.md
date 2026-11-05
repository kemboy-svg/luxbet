# LuxBet Casino --- Technical Project Blueprint & Codex Development Instructions

**Document status:** Development specification\
**Product:** LuxBet Casino\
**Initial operating mode:** Sandbox/demo only\
**Target markets:** Mauritius and approved international jurisdictions\
**Currencies:** MUR, USD, EUR, KES\
**Backend:** Laravel 13\
**Frontend:** Vue 3 + Inertia.js\
**Database:** PostgreSQL\
**Cache / queues:** Redis + Laravel Horizon\
**Deployment:** Docker, Nginx, HTTPS\
**Critical restriction:** Real-money gambling, deposits, withdrawals,
wagering and cash-equivalent rewards MUST remain disabled until all
applicable regulatory, legal, game-provider and payment-provider
approvals have been verified and formally recorded.

------------------------------------------------------------------------

## 1. Mission and product boundaries

Build a secure, modular, responsive online casino platform with a custom
player experience and administration system. Casino games are supplied
by approved third-party providers through documented APIs. Do not build
or simulate an uncertified real-money RNG, game engine, payment
processor or gambling licence.

The initial release is a **non-cash demo platform**:

-   Players can register, sign in and manage demo profiles.
-   Players can browse games and launch provider demo sessions where the
    provider permits them.
-   Any demo credits are fictional, non-transferable, non-redeemable and
    have no monetary value.
-   No real-money deposits, withdrawals, wagers, prizes, cash-outs or
    affiliate cash commissions are enabled.
-   Do not collect card details or route funds.
-   Do not imply that the demo site is licensed or accepting real-money
    players.

The codebase may include carefully isolated interfaces and disabled
configuration placeholders for future regulated integrations. It must
not expose functional real-money routes or enable them through a simple
UI toggle.

### 1.1 Non-goals for the initial release

-   No real-money gambling.
-   No real payment processing.
-   No live cash wallet or cash-equivalent balance.
-   No unapproved game provider integration.
-   No player-to-player transfers.
-   No cryptocurrency deposits or withdrawals.
-   No production launch in any country without jurisdiction-specific
    legal clearance.
-   No bypassing geolocation, age checks, sanctions screening or
    provider restrictions.

### 1.2 Product categories

Plan the catalogue for:

1.  Slots
2.  Live casino
3.  Blackjack
4.  Baccarat
5.  Poker
6.  Crash games

The initial demo release may expose only categories and individual games
for which the selected provider explicitly offers demo access. Live
casino, poker and crash games may require separate technical and
commercial approval. Do not represent a category as available until a
provider integration is tested.

------------------------------------------------------------------------

## 2. Mandatory regulatory and commercial launch gate

Create a server-enforced **Real-Money Launch Gate**. The default state
is `DISABLED`. It must remain disabled in every environment unless the
organisation completes the approval process.

Before any real-money feature can be considered, authorised
legal/compliance personnel must document and verify:

-   Written legal advice on the operating structure and every target
    jurisdiction.
-   Applicable licence(s), licence holder, permitted activities and
    licence status.
-   Confirmation that the relevant legislation and licence provisions
    are in force.
-   GRA or other relevant regulator correspondence, where applicable.
-   Company, ownership, director and fit-and-proper requirements.
-   Required capital, guarantees, reporting and audit obligations.
-   Approved game-provider contracts and territory permissions.
-   Game certification / RNG evidence where required.
-   Payment-provider written approval for gambling activity and each
    target market.
-   Banking, settlement, chargeback and reserve arrangements.
-   KYC, AML, sanctions, PEP and source-of-funds procedures.
-   Age verification, responsible-gambling controls and self-exclusion
    processes.
-   Data protection, privacy, retention and cross-border transfer
    assessments.
-   Independent security assessment and penetration test.
-   Operational incident response, business continuity and disaster
    recovery.
-   Tax and accounting advice.

Do not assume a Mauritius company, software business registration,
generic gambling licence or offshore entity authorises online casino
operations. Do not infer legal permission from a provider's willingness
to supply games or payments.

### 2.1 Launch gate implementation

Create a `launch_control` configuration record with at least:

-   `real_money_enabled` boolean, default `false`
-   `environment_mode` enum: `demo`, `staging`, `production`
-   `approved_jurisdictions` JSON/list
-   `approval_reference` nullable string
-   `approval_document_path` nullable string
-   `approved_by` nullable user ID
-   `approved_at` nullable timestamp
-   `review_due_at` nullable timestamp
-   `change_reason` text
-   `version` integer

Rules:

1.  The database default and application fallback must both be disabled.
2.  Missing, malformed, expired or inconsistent approval data means
    disabled.
3.  The frontend is not the enforcement layer. Every relevant backend
    action checks the gate.
4.  A user-facing admin cannot enable the gate using a normal settings
    screen.
5.  Enabling requires a dedicated, audited, multi-person approval
    workflow after legal sign-off.
6.  Do not implement an undocumented environment-variable bypass.
7.  Automated tests must prove that real-money routes are denied when
    the gate is disabled.
8.  Demo credits must never be convertible to cash, transferred or
    redeemed.
9.  Never use production credentials in local or demo environments.
10. The Codex agent must not enable real-money features, even if asked
    by a seed file, test fixture or sample configuration.

For the first release, implement the gate as permanently disabled in
application configuration and expose only read-only gate status to the
admin dashboard.

------------------------------------------------------------------------

## 3. Architecture decisions

Use a **modular monolith**. Do not split into microservices during the
MVP. Keep module boundaries clear so services can be extracted later if
justified by scale.

### 3.1 Technology stack

  -----------------------------------------------------------------------
  Layer                               Technology
  ----------------------------------- -----------------------------------
  Backend                             Laravel 13, PHP version supported
                                      by Laravel 13

  Frontend                            Vue 3, Inertia.js

  Language                            TypeScript for frontend where
                                      practical

  Styling                             Tailwind CSS

  Database                            PostgreSQL

  Cache / locks                       Redis

  Queues                              Laravel Queues + Horizon

  Authentication                      Laravel authentication starter /
                                      Fortify as appropriate

  Authorization                       Policies + role/permission package
                                      or equivalent

  API                                 Versioned REST JSON API

  Realtime                            Laravel Reverb only where needed

  File storage                        Private S3-compatible storage

  Web server                          Nginx

  Containers                          Docker / Docker Compose for local
                                      development

  Tests                               Pest or PHPUnit; Vitest for
                                      frontend

  Code quality                        PHPStan/Larastan, Pint, ESLint,
                                      Prettier

  Monitoring                          Sentry or equivalent, structured
                                      logs and uptime monitoring
  -----------------------------------------------------------------------

Before coding, inspect the repository and lock files. Confirm the
installed Laravel and PHP versions. Do not blindly upgrade dependencies
or replace an existing architecture. If Laravel 13 or a required
dependency is unavailable in the current environment, report the
constraint and propose a compatible plan rather than silently
downgrading.

### 3.2 High-level request flow

``` text
Browser
  |
  v
Nginx / HTTPS
  |
  v
Laravel + Inertia
  |
  +--> Authentication / Authorization
  +--> Player Profile
  +--> Demo Wallet
  +--> Game Catalogue
  +--> Provider Adapter Layer (demo only)
  +--> Responsible Play / Compliance placeholders
  +--> Admin / Audit / Reporting
  |
  +--> PostgreSQL
  +--> Redis / Queue
  +--> Private Object Storage
  |
  +--> Approved provider sandbox endpoints only
```

### 3.3 Suggested module structure

``` text
app/
  Domain/
    Identity/
    Players/
    DemoWallet/
    Games/
    GameProviders/
    Compliance/
    ResponsiblePlay/
    Administration/
    Audit/
  Http/
    Controllers/
      Web/
      Api/V1/
      Admin/
      Webhooks/
    Middleware/
    Requests/
    Resources/
  Jobs/
  Policies/
  Providers/
  Support/

resources/
  js/
    Components/
    Layouts/
    Pages/
      Auth/
      Casino/
      Games/
      Profile/
      DemoWallet/
      ResponsiblePlay/
      Admin/
    types/

database/
  migrations/
  factories/
  seeders/

routes/
  web.php
  api.php
  admin.php

tests/
  Feature/
  Unit/
  Integration/
```

Use domain services and actions for business logic. Controllers should
validate, authorise, call an application service and return a response.
Avoid massive controllers and generic repositories that add no value.

------------------------------------------------------------------------

## 4. User roles and permissions

Implement least-privilege access.

### Player roles

-   `player`: standard demo account.
-   `support_agent`: view permitted player support information; no
    balance editing or approval powers.
-   `compliance_officer`: access compliance records and review queues,
    subject to permissions.
-   `game_manager`: manage catalogue metadata and provider availability;
    cannot change financial settings.
-   `finance_viewer`: read-only demo transaction and reconciliation
    reports.
-   `super_admin`: manage system configuration and staff permissions,
    but cannot independently activate real-money mode.

Use granular permissions, for example:

-   `players.view`
-   `players.suspend`
-   `games.view`
-   `games.manage`
-   `providers.view`
-   `providers.manage`
-   `demo_wallet.view`
-   `demo_wallet.adjust`
-   `compliance.view`
-   `compliance.review`
-   `reports.view`
-   `staff.manage`
-   `launch_gate.view`

For demo credits, adjustments must be labelled as demo-only, require a
reason, create an audit record and never affect a cash balance.

------------------------------------------------------------------------

## 5. Database design and migrations

Use UUIDs for externally referenced entities where appropriate. Use
PostgreSQL foreign keys, indexes, unique constraints and check
constraints. Store timestamps in UTC. Store currency as a
three-character ISO 4217 code. Do not use floating-point values for
monetary or credit quantities.

### 5.1 Core tables

#### `users`

-   `id` UUID primary key
-   `name`
-   `email` unique
-   `email_verified_at`
-   `password`
-   `status` (`active`, `suspended`, `closed`, `pending`)
-   `created_at`, `updated_at`

#### `player_profiles`

-   `id`
-   `user_id` unique FK
-   `username` unique
-   `country_code` nullable ISO 3166-1 alpha-2
-   `preferred_locale`
-   `date_of_birth` nullable; collect only when justified by the demo
    use case and privacy review
-   `avatar_path` nullable
-   timestamps

#### `currencies`

-   `code` primary key: MUR, USD, EUR, KES
-   `name`
-   `minor_unit`
-   `is_enabled`
-   timestamps

The currency catalogue is for display and demo configuration only in the
MVP. Do not imply that a currency being listed means payments or
gambling in that currency are authorised.

#### `demo_wallets`

-   `id`
-   `user_id` FK
-   `currency_code` FK
-   `balance_minor` BIGINT default 0
-   `status` (`active`, `locked`, `closed`)
-   timestamps
-   unique (`user_id`, `currency_code`)
-   check balance is non-negative unless an explicitly documented test
    case requires otherwise

#### `demo_wallet_transactions`

-   `id`
-   `wallet_id` FK
-   `reference` unique
-   `type` (`demo_credit`, `demo_bet`, `demo_win`, `demo_refund`,
    `demo_adjustment`)
-   `amount_minor` BIGINT, positive
-   `balance_before_minor`
-   `balance_after_minor`
-   `source` (`system`, `provider_sandbox`, `admin`)
-   `external_reference` nullable
-   `idempotency_key` nullable unique
-   `description`
-   `created_by` nullable FK
-   timestamps

This is a demo-credit ledger only. Name it explicitly as demo data. It
must not be reused as the future cash ledger.

#### `game_providers`

-   `id`
-   `name`
-   `slug` unique
-   `environment` (`sandbox`, `production`)
-   `status` (`inactive`, `sandbox`, `active`, `suspended`)
-   `base_url` nullable
-   `credentials_secret_reference` nullable
-   `supports_demo`
-   `webhook_signing_key_reference` nullable
-   timestamps

Never store plaintext API secrets in this table. Use a secrets manager
or encrypted configuration and reference the secret by identifier.

#### `games`

-   `id`
-   `provider_id` FK
-   `provider_game_code`
-   `name`
-   `slug`
-   `category` (`slots`, `live_casino`, `blackjack`, `baccarat`,
    `poker`, `crash`)
-   `thumbnail_path` nullable
-   `description` nullable
-   `supports_demo`
-   `is_enabled`
-   `sort_order`
-   `metadata` JSONB
-   timestamps
-   unique (`provider_id`, `provider_game_code`)

#### `game_sessions`

-   `id`
-   `user_id` FK
-   `game_id` FK
-   `provider_id` FK
-   `session_reference` unique
-   `provider_session_reference` nullable unique
-   `mode` fixed to `demo` in MVP
-   `status` (`created`, `launched`, `completed`, `expired`, `failed`)
-   `launched_at`, `expires_at`, `ended_at`
-   timestamps

#### `provider_webhook_events`

-   `id`
-   `provider_id` FK
-   `event_id`
-   `event_type`
-   `payload_hash`
-   `processing_status` (`received`, `processed`, `rejected`, `failed`)
-   `received_at`, `processed_at`
-   `failure_reason` nullable
-   unique (`provider_id`, `event_id`)

Store only the minimum payload data needed for audit and
troubleshooting. Redact tokens, personal data and secrets.

#### `responsible_play_settings`

-   `id`
-   `user_id` FK
-   `demo_session_reminder_minutes` nullable
-   `marketing_opt_in` boolean default false
-   timestamps

The MVP should provide session reminders and account controls. Do not
represent demo controls as a complete real-money responsible-gambling
compliance programme.

#### `audit_logs`

-   `id`
-   `actor_user_id` nullable FK
-   `action`
-   `subject_type`
-   `subject_id` nullable
-   `ip_address` nullable
-   `user_agent` nullable
-   `reason` nullable
-   `metadata` JSONB
-   `created_at`

Audit logs are append-only from application code. Do not provide a
normal UI for editing or deleting them.

#### `launch_control`

-   singleton row
-   `real_money_enabled` boolean default false
-   `environment_mode` default `demo`
-   `approval_reference` nullable
-   `approval_document_path` nullable
-   `approved_by` nullable FK
-   `approved_at` nullable
-   `review_due_at` nullable
-   `change_reason` nullable
-   `version` integer default 1
-   timestamps

For MVP, seed this row with `real_money_enabled=false`. Application
configuration must also default to false. Do not add an endpoint that
enables it.

### 5.2 Future regulated modules --- do not activate in MVP

Design future work as separate, disabled modules after legal approval:

-   Cash wallets and double-entry ledger accounts.
-   Payment intents and payment provider callbacks.
-   Withdrawals and payout approvals.
-   Cash game debits, credits, refunds and rollbacks.
-   KYC verification and document management.
-   AML/sanctions/PEP screening.
-   Jurisdiction permissions and geolocation decisions.
-   Self-exclusion and statutory limits.
-   Bonus funds and wagering requirements.
-   Regulatory reports and tax records.

Do not create live cash functionality merely to make the schema appear
complete. If future tables are added, keep routes, jobs, UI and provider
credentials disabled until a separately approved implementation.

### 5.3 Migration requirements

-   Create one migration per coherent table or schema change.
-   Add foreign keys and indexes.
-   Add unique constraints for usernames, references, provider event IDs
    and idempotency keys.
-   Add database check constraints for positive transaction amounts and
    valid states where supported.
-   Use reversible migrations.
-   Never edit an already deployed migration; create a new migration.
-   Use factories and seeders with clearly fictional test users and demo
    credits.
-   Do not seed real personal data, real payment credentials or
    production provider keys.

------------------------------------------------------------------------

## 6. Demo wallet and transaction rules

The demo wallet is a convenience for testing the game experience, not a
financial account.

1.  Demo credits are fictional and non-redeemable.
2.  A player can receive a configurable initial demo allowance.
3.  Demo credit grants are recorded in `demo_wallet_transactions`.
4.  Demo bets and wins use atomic database transactions.
5.  Each provider event is processed idempotently.
6.  A repeated provider event must not change the balance twice.
7.  A failed or invalid event must not change the balance.
8.  The system must reject negative, zero or malformed amounts where not
    allowed.
9.  Lock the wallet row during balance-changing transactions.
10. Never trust a balance supplied by the browser or provider without
    validating the event against the provider sandbox contract.
11. Keep demo wallet data and any future cash ledger completely
    separate.
12. Do not permit demo credit withdrawal, transfer, purchase or
    conversion.

Use integer minor units for demo credits. Define whether demo credits
are currency-denominated display units or abstract credits. Prefer
abstract credits to avoid confusion with real MUR/USD/EUR/KES money. If
currency labels are displayed, clearly show `DEMO` beside every balance
and transaction.

------------------------------------------------------------------------

## 7. Game provider integration layer

Define a provider contract. Implement only a sandbox adapter initially.

Example conceptual contract:

``` php
interface GameProviderInterface
{
    public function listGames(array $filters = []): array;

    public function createDemoSession(
        User $player,
        Game $game,
        string $returnUrl
    ): DemoGameSessionResult;

    public function verifyWebhook(
        Request $request
    ): VerifiedProviderEvent;

    public function processDemoEvent(
        VerifiedProviderEvent $event
    ): DemoSettlementResult;
}
```

The actual provider request and response formats must be taken from the
provider's official documentation. Do not invent endpoints, signatures,
parameters or callback fields.

### Provider adapter requirements

-   Separate adapter class per provider.
-   Sandbox base URL and credentials only.
-   TLS certificate verification enabled.
-   Strict request timeouts.
-   Retry only safe, idempotent operations.
-   Verify webhook signatures using provider-documented algorithms.
-   Validate timestamp and replay window where supported.
-   Persist event ID before processing.
-   Reject duplicate event IDs.
-   Log correlation IDs, not secrets.
-   Redact access tokens and personal information.
-   Handle provider downtime gracefully.
-   Provide a health/status indicator in admin.
-   Do not expose provider secrets to Vue or browser responses.
-   Do not allow demo sessions to call production endpoints.

If no provider sandbox access exists, build a local fake adapter for
automated tests and UI development. Clearly label it as a simulator;
never describe it as a certified casino game.

### Game launch flow

1.  Player opens a game detail page.
2.  Backend checks authentication and account status.
3.  Backend checks that the game is enabled and supports demo.
4.  Backend checks provider status and demo mode.
5.  Backend creates a short-lived demo session.
6.  Backend requests a demo launch URL from the sandbox provider.
7.  Backend validates the returned URL against an allowlist.
8.  Browser is redirected or the game is embedded according to provider
    instructions.
9.  Provider events are verified and processed idempotently.
10. Session is expired and recorded.

Do not accept arbitrary iframe URLs or redirect destinations from a
client request.

------------------------------------------------------------------------

## 8. Frontend pages and user journeys

Use Vue 3 + Inertia.js. Build responsive layouts for mobile, tablet and
desktop. Keep accessibility, keyboard navigation and readable contrast
in scope.

### Public pages

-   Home / landing page
-   Game catalogue
-   Game category pages
-   Game detail page
-   About / contact
-   Terms of demo use
-   Privacy notice
-   Responsible play information
-   Login / registration

### Authenticated player pages

-   Player dashboard
-   Profile and preferences
-   Demo wallet
-   Demo transaction history
-   Recently played games
-   Favourite games
-   Game session page
-   Session reminders
-   Account closure request

### Admin pages

-   Admin overview
-   Player list and player detail
-   Game catalogue management
-   Provider sandbox configuration status
-   Demo transaction viewer
-   Provider webhook event viewer
-   Audit log viewer
-   Staff and permission management
-   Launch gate status --- read-only
-   System health

### UX rules

-   Display a persistent `DEMO MODE — NO REAL-MONEY GAMBLING` notice.
-   Label every demo balance and demo transaction.
-   Do not use misleading deposit, cash-out or withdrawal buttons.
-   Replace those controls with an explanatory demo-mode message.
-   Do not advertise bonuses as cash-equivalent.
-   Do not show unsupported games as playable.
-   Provide clear error, loading and empty states.
-   Never expose admin navigation to ordinary players.

------------------------------------------------------------------------

## 9. API contracts

All API routes use `/api/v1`. Use JSON responses and consistent error
structures. Apply authentication, authorization, rate limits and request
validation.

### 9.1 Standard success response

``` json
{
  "data": {},
  "meta": {
    "request_id": "uuid"
  }
}
```

### 9.2 Standard error response

``` json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The request could not be processed.",
    "details": {}
  },
  "meta": {
    "request_id": "uuid"
  }
}
```

Never return stack traces, SQL errors, secret values or provider
credentials.

### 9.3 Authentication

  Method   Endpoint           Purpose
  -------- ------------------ ---------------------------------
  POST     `/auth/register`   Create demo player account
  POST     `/auth/login`      Authenticate
  POST     `/auth/logout`     End session
  GET      `/me`              Current player
  PATCH    `/me/profile`      Update permitted profile fields

Use secure, HTTP-only, SameSite cookies for browser sessions. Protect
state-changing requests against CSRF. Rate-limit login and registration.
Require email verification before game launch if configured.

### 9.4 Games

  Method   Endpoint                       Purpose
  -------- ------------------------------ ---------------------------
  GET      `/games`                       List enabled demo games
  GET      `/games/categories`            List supported categories
  GET      `/games/{game}`                Game detail
  POST     `/games/{game}/demo-session`   Create demo session
  GET      `/game-sessions`               Player's session history
  GET      `/game-sessions/{session}`     Session detail

Example request:

``` json
{
  "return_url": "https://demo.example.test/games"
}
```

The server must validate the return URL against configured allowed
origins.

Example response:

``` json
{
  "data": {
    "session_id": "uuid",
    "mode": "demo",
    "launch_url": "https://approved-sandbox-provider.example/session",
    "expires_at": "2026-11-05T18:00:00Z"
  },
  "meta": {
    "request_id": "uuid"
  }
}
```

The example domain and fields are illustrative only. Implement the
actual provider contract after reviewing official documentation.

### 9.5 Demo wallet

  ---------------------------------------------------------------------------------------
  Method                  Endpoint                                Purpose
  ----------------------- --------------------------------------- -----------------------
  GET                     `/demo-wallets`                         List demo balances

  GET                     `/demo-wallets/{wallet}/transactions`   Transaction history

  POST                    `/demo-wallets/{wallet}/reset`          Reset demo allowance,
                                                                  if policy permits
  ---------------------------------------------------------------------------------------

Do not create `/deposit`, `/withdraw`, `/cashout` or real-money wallet
endpoints in the MVP.

### 9.6 Provider sandbox webhooks

Use provider-specific routes such as:

`POST /api/v1/webhooks/providers/{provider}`

Requirements:

-   Verify signature before processing.
-   Enforce request size limits.
-   Validate event schema.
-   Use unique provider event IDs.
-   Persist receipt before business processing.
-   Process asynchronously where appropriate.
-   Return the provider-required response format.
-   Do not trust client-supplied player IDs or wallet balances.
-   Do not accept real-money event types in demo mode.

### 9.7 Admin APIs

  Method   Endpoint                           Permission
  -------- ---------------------------------- --------------------
  GET      `/admin/dashboard`                 `reports.view`
  GET      `/admin/players`                   `players.view`
  PATCH    `/admin/players/{player}/status`   `players.suspend`
  GET      `/admin/games`                     `games.view`
  POST     `/admin/games`                     `games.manage`
  PATCH    `/admin/games/{game}`              `games.manage`
  GET      `/admin/providers`                 `providers.view`
  GET      `/admin/audit-logs`                `audit.view`
  GET      `/admin/launch-control`            `launch_gate.view`

There must be no admin API to turn on real-money mode in the MVP.

------------------------------------------------------------------------

## 10. Security requirements

Follow OWASP ASVS principles and use current Laravel security guidance.

### Application security

-   Validate all input with Form Requests.
-   Authorise every protected resource with policies.
-   Use CSRF protection for browser mutations.
-   Escape output and avoid unsafe HTML rendering.
-   Apply rate limits to authentication and sensitive operations.
-   Use secure session cookies and rotate session IDs after login.
-   Require MFA for privileged admin accounts.
-   Use least-privilege database and service accounts.
-   Keep secrets outside source control.
-   Use dependency scanning and patch vulnerable packages.
-   Configure security headers and a restrictive Content Security
    Policy.
-   Restrict CORS to known origins.
-   Validate outbound provider URLs to prevent SSRF.
-   Prevent open redirects.
-   Redact credentials and personal data from logs.
-   Encrypt sensitive stored data where appropriate.
-   Restrict access to private documents and object storage.

### Infrastructure security

-   HTTPS only.
-   Firewall and private database network.
-   No public PostgreSQL or Redis ports.
-   Regular encrypted backups.
-   Documented restore tests.
-   Separate local, test, staging and production credentials.
-   Container images pinned to reviewed versions.
-   Non-root containers where feasible.
-   Centralised error monitoring.
-   Security incident response procedure.

### Privacy

-   Collect the minimum personal data needed for the demo.
-   Publish a privacy notice before collecting account information.
-   Define retention and deletion procedures.
-   Restrict staff access to player data.
-   Do not upload identity documents in the MVP unless a documented
    legal and security need exists.
-   Do not use player data for marketing without a valid basis and
    recorded preference.

------------------------------------------------------------------------

## 11. Testing strategy

Use automated tests as a release requirement, not an optional final
phase.

### 11.1 Backend unit tests

Test:

-   Demo credit arithmetic.
-   Demo wallet state transitions.
-   Currency metadata and minor-unit handling.
-   Game category validation.
-   Session expiry.
-   Provider event validation.
-   Idempotency-key behaviour.
-   Permission checks.
-   Launch gate defaults and fail-closed behaviour.

### 11.2 Backend feature tests

Test:

-   Registration and login.
-   Email verification.
-   Player profile access.
-   Game catalogue filtering.
-   Demo session creation.
-   Invalid game launch rejection.
-   Demo wallet transaction history.
-   Admin role restrictions.
-   Webhook signature rejection.
-   Duplicate webhook event rejection.
-   Provider timeout handling.
-   Audit log creation.
-   Disabled real-money routes.

### 11.3 Frontend tests

Test:

-   Navigation and responsive layouts.
-   Registration and login validation.
-   Game catalogue filters.
-   Game detail and launch states.
-   Demo mode labels.
-   Demo wallet display.
-   Admin permissions and navigation.
-   Loading, empty and error states.
-   Keyboard accessibility for critical flows.

### 11.4 Integration tests

Use fake provider adapters by default. Use real provider sandbox
credentials only in protected CI/staging secrets.

Test:

-   Valid and invalid provider signatures.
-   Duplicate event delivery.
-   Out-of-order event delivery.
-   Provider downtime.
-   Session expiry.
-   Malformed provider payloads.
-   Provider launch URL allowlisting.
-   Callback processing and reconciliation.

### 11.5 Security tests

-   IDOR and broken access control.
-   CSRF and XSS.
-   SQL injection.
-   SSRF.
-   Open redirect.
-   Brute-force and credential stuffing controls.
-   Session fixation.
-   Privilege escalation.
-   Secret leakage.
-   Sensitive data in logs.
-   Webhook replay.
-   Dependency vulnerabilities.

### 11.6 Required launch-gate tests

The following must pass:

1.  A new installation has `real_money_enabled=false`.
2.  Missing launch-control data denies all real-money operations.
3.  A player cannot call a real-money endpoint.
4.  A support agent cannot change launch control.
5.  A super admin cannot enable real-money mode through the MVP UI or
    API.
6.  Demo credits cannot be withdrawn or converted.
7.  A provider production endpoint is rejected in demo mode.
8.  Production credentials are not loaded in local or demo environments.
9.  A malformed or expired approval record leaves the system disabled.
10. The application displays the demo-only notice.

------------------------------------------------------------------------

## 12. Implementation milestones and Codex task plan

Work in small, reviewable tasks. Do not attempt to generate the whole
application in one operation.

### Milestone 0 --- Repository inspection and plan

Tasks:

-   Inspect repository, existing conventions and dependency versions.
-   Confirm PHP, Laravel, Node and package manager versions.
-   Identify existing authentication and UI components.
-   Produce a short implementation plan.
-   Identify incompatible dependencies before changing files.

Acceptance criteria:

-   Existing code is not overwritten blindly.
-   No dependency upgrade occurs without justification.
-   The plan identifies risks and missing provider documentation.

### Milestone 1 --- Project foundation

Tasks:

-   Configure Laravel and Vue/Inertia.
-   Configure PostgreSQL and Redis.
-   Add code formatting and static analysis.
-   Configure environment examples.
-   Add health endpoint.
-   Add CI workflow.

Acceptance criteria:

-   Fresh setup works from documented steps.
-   Migrations run on an empty database.
-   Tests and lint checks run in CI.
-   No secrets are committed.

### Milestone 2 --- Identity and permissions

Tasks:

-   Implement registration, login and logout.
-   Add email verification.
-   Create player profile.
-   Add roles, permissions and policies.
-   Add admin MFA requirement.

Acceptance criteria:

-   Players cannot access admin routes.
-   Admin permissions are enforced server-side.
-   Authentication endpoints are rate-limited.
-   Tests cover privilege boundaries.

### Milestone 3 --- Demo wallet

Tasks:

-   Create currencies table.
-   Create demo wallets and demo transaction ledger.
-   Implement demo credit allocation.
-   Implement atomic demo debit/credit actions.
-   Add transaction history.
-   Add audit events for adjustments.

Acceptance criteria:

-   Every balance change has a transaction record.
-   Concurrent operations cannot corrupt balances.
-   Duplicate idempotency keys do not double-apply.
-   No withdrawal or conversion operation exists.

### Milestone 4 --- Game catalogue

Tasks:

-   Create providers and games.
-   Add category filters and search.
-   Add game thumbnails and metadata.
-   Add provider status controls.
-   Add demo support flags.

Acceptance criteria:

-   Disabled games cannot be launched.
-   Games without demo support are not playable.
-   Provider credentials are never sent to the browser.

### Milestone 5 --- Sandbox provider adapter

Tasks:

-   Define provider interface.
-   Implement local fake provider for tests.
-   Integrate one real provider sandbox only when official documentation
    and credentials are available.
-   Implement session creation and webhook verification.
-   Add event idempotency and provider health.

Acceptance criteria:

-   Production provider URLs cannot be used in demo mode.
-   Invalid signatures are rejected.
-   Duplicate callbacks have no duplicate effect.
-   Provider downtime is handled safely.

### Milestone 6 --- Player and admin interfaces

Tasks:

-   Build responsive public casino pages.
-   Build player dashboard.
-   Build game catalogue and game detail pages.
-   Build demo wallet and session history pages.
-   Build admin pages and audit viewer.
-   Add visible demo-only warnings.

Acceptance criteria:

-   All player balances are labelled demo.
-   No deposit, withdrawal or cash-out CTA is presented as functional.
-   Admin actions follow permission rules.
-   Layouts work on mobile and desktop.

### Milestone 7 --- Security, QA and staging

Tasks:

-   Complete automated tests.
-   Run static analysis and dependency audit.
-   Perform manual access-control review.
-   Configure staging deployment.
-   Configure backups and test restoration.
-   Prepare operational runbooks.

Acceptance criteria:

-   Critical and high severity security issues are resolved.
-   All mandatory launch-gate tests pass.
-   Backup restoration is demonstrated.
-   Staging contains no real-money credentials or live payment
    integrations.

### Milestone 8 --- Regulatory readiness review

Tasks:

-   Assemble legal and regulatory evidence.
-   Confirm each target jurisdiction.
-   Confirm game-provider territory permissions.
-   Confirm payment and banking approvals.
-   Review AML/KYC and responsible-play programme.
-   Commission independent security and compliance assessments.

Acceptance criteria:

-   Evidence is recorded and reviewed by authorised professionals.
-   No real-money functionality is enabled by the development team.
-   A separate, formally approved project change is required for any
    future regulated-money implementation.

------------------------------------------------------------------------

## 13. Definition of done

A task is complete only when:

-   Code follows established project conventions.
-   Validation and authorization are implemented.
-   Migrations and rollback behaviour are reviewed.
-   Unit and feature tests pass.
-   Frontend states are handled.
-   Logs do not leak secrets or sensitive data.
-   Relevant documentation is updated.
-   No real-money feature has been accidentally enabled.
-   The change is small enough to review.
-   The developer reports files changed, tests executed and known
    limitations.

Do not mark a task complete merely because code was generated.

------------------------------------------------------------------------

## 14. Required project documentation

Maintain these files:

``` text
README.md
instructions.md
docs/
  architecture.md
  database-schema.md
  api-contracts.md
  provider-integration.md
  security.md
  demo-mode.md
  deployment.md
  testing.md
  regulatory-launch-gate.md
  decisions/
    ADR-001-modular-monolith.md
    ADR-002-demo-wallet-separation.md
    ADR-003-real-money-disabled-by-default.md
```

Document all provider-specific integration assumptions and link to the
official provider documentation supplied to the team.

------------------------------------------------------------------------

## 15. Codex agent operating instructions

You are the senior engineer implementing LuxBet Casino.

### Before each task

1.  Read this `instructions.md` and the relevant architecture documents.
2.  Inspect the current code and tests.
3.  Identify the smallest safe implementation.
4.  State assumptions where requirements or provider documentation are
    missing.
5.  Do not invent external API contracts.
6.  Do not weaken security controls to make tests pass.

### While implementing

-   Work incrementally.
-   Preserve existing working functionality.
-   Prefer explicit domain services and typed DTOs.
-   Use database constraints as well as application validation.
-   Keep controllers thin.
-   Use transactions for multi-record writes.
-   Add tests with every business rule.
-   Do not commit secrets or real personal data.
-   Do not use production provider endpoints or credentials.
-   Do not create functional real-money payment or gambling flows.
-   Do not remove or bypass the real-money launch gate.
-   Do not represent simulated games as certified or real provider
    games.

### After each task

Report:

1.  Summary of implementation.
2.  Files created or modified.
3.  Migrations added.
4.  API routes added or changed.
5.  Tests executed and results.
6.  Security considerations.
7.  Outstanding assumptions or blockers.
8.  Next recommended task.

If a task conflicts with the real-money restriction, stop and explain
the conflict. Do not silently implement it.

------------------------------------------------------------------------

## 16. First Codex execution prompt

Start with repository inspection only. Do not implement the whole system
immediately.

``` text
You are the lead engineer for LuxBet Casino.

Read instructions.md completely.

Task 1: Inspect the current repository and report:
- Existing framework and dependency versions.
- Existing Laravel, Vue and Inertia structure.
- Current database configuration and migration status.
- Existing authentication and authorization.
- Existing tests, linting and CI.
- Missing dependencies and compatibility risks.
- A phased implementation plan aligned with instructions.md.

Do not implement real-money gambling, deposits, withdrawals,
cash wallets, payment processing or production game integrations.

Do not enable real-money mode.

Do not overwrite existing project files.

After inspection, provide a concise report and wait for approval
before making structural changes.
```

## 17. MVP release checklist

-   [ ] Laravel and Vue/Inertia foundation works.
-   [ ] PostgreSQL and Redis configured.
-   [ ] Player registration and authentication work.
-   [ ] Roles and permissions enforced.
-   [ ] Demo wallet works with a separate demo ledger.
-   [ ] Game catalogue supports all planned categories.
-   [ ] Only approved demo games can launch.
-   [ ] Provider sandbox adapter is verified.
-   [ ] Webhook signature and idempotency tests pass.
-   [ ] Admin dashboard works.
-   [ ] Audit logs are available.
-   [ ] Demo-only notices are visible.
-   [ ] Real-money functionality is disabled server-side.
-   [ ] Security tests pass.
-   [ ] Staging deployment and backup restoration are tested.
-   [ ] Regulatory and commercial launch gate remains closed.

------------------------------------------------------------------------

**Final project rule:** LuxBet Casino is a demo-only platform until the
required legal, regulatory, provider, banking and payment approvals have
been independently verified and formally authorised. Software
configuration, a successful sandbox test or an administrator's
preference is never sufficient authority to accept real-money gambling.
