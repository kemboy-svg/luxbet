# LuxBet Casino

A secure, modular casino platform built with Laravel 13, Vue 3, Inertia and
PostgreSQL.

> **Demo only.** Real-money gambling, deposits, withdrawals, wagering and
> cash-equivalent rewards are disabled and enforced server-side. Demo credits
> are fictional and have no monetary value. See
> [docs/regulatory-launch-gate.md](docs/regulatory-launch-gate.md).

The full specification lives in [instructions.md](instructions.md).

## Requirements

- PHP 8.3+ with `pdo_pgsql`, `redis`, `intl`, `mbstring`, `zip`
- Composer 2
- Node.js 22 and npm
- PostgreSQL 16 and Redis 7 (or Docker, which provides both)

## Local setup

```bash
git clone https://github.com/kemboy-svg/luxbet.git
cd luxbet

# 1. Start PostgreSQL, Redis and Mailpit (or use your own local services)
docker compose up -d pgsql redis mailpit

# 2. Install dependencies
composer install
npm install

# 3. Configure the environment
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # seeds the local simulator games and test@example.com / password

# 4. Run the app (Laravel server, queue, logs and Vite together)
composer run dev
```

Open http://localhost:8000.

Email verification is **off** for now (`LUXBET_REQUIRE_EMAIL_VERIFICATION=false`):
players can use the site straight after registering and no verification
email is sent. Other emails (password reset) go to `storage/logs/laravel.log`
with `MAIL_MAILER=log`, or to Mailpit at http://localhost:8025 with
`MAIL_MAILER=smtp` and `docker compose up -d mailpit`.

### Staff accounts

Everyone who registers is a demo player. To make a local staff account,
register normally, then:

```bash
php artisan tinker --execute="App\Models\User::where('email', 'you@example.test')->firstOrFail()->assignRole('super_admin');"
```

Staff must turn on two-factor authentication (Settings → Security) before the
admin area at `/admin` opens. Roles and permissions are listed in
[docs/security.md](docs/security.md).

The Docker stack also includes `app` (PHP-FPM) and `nginx` services if you
prefer to run everything in containers: `docker compose up -d`, then
http://localhost:8080.

## What's in the app

- **Players:** game lobby, demo play with the local simulator, demo wallet,
  dashboard with recent activity, favourites, play history, profile
  preferences and responsible-play settings (session reminders, marketing
  opt-in).
- **Staff (`/admin`):** overview with demo-play metrics, players (detail,
  status, demo-credit adjustments), games, providers, provider events, demo
  ledger, audit log, system health, staff management and the read-only
  launch-gate status. Each screen needs its own permission.

## Checks

```bash
composer test          # Pint (style), Larastan (static analysis), PHPUnit
npm run check          # lint and formatting
npm run types:check    # vue-tsc
php artisan luxbet:launch-gate   # read-only real-money gate status
```

Tests run against a PostgreSQL database named `luxbet_testing` (created
automatically by the Docker stack). See [docs/testing.md](docs/testing.md).

## Documentation

| Document | Topic |
| --- | --- |
| [docs/architecture.md](docs/architecture.md) | Modular monolith, modules, request flow |
| [docs/regulatory-launch-gate.md](docs/regulatory-launch-gate.md) | How real money stays disabled |
| [docs/demo-mode.md](docs/demo-mode.md) | What demo mode means for users and code |
| [docs/api-contracts.md](docs/api-contracts.md) | `/api/v1` envelopes and endpoints |
| [docs/database-schema.md](docs/database-schema.md) | Tables and constraints |
| [docs/provider-integration.md](docs/provider-integration.md) | Game-provider adapters |
| [docs/security.md](docs/security.md) | Security controls |
| [docs/testing.md](docs/testing.md) | Running and writing tests |
| [docs/deployment.md](docs/deployment.md) | Environments and deployment |
| [docs/decisions/](docs/decisions/) | Architecture decision records |
