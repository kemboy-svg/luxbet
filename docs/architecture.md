# Architecture

LuxBet is a **modular monolith** (see
[ADR-001](decisions/ADR-001-modular-monolith.md)): one Laravel 13 application
with clear module boundaries, so modules can be extracted later only if scale
justifies it.

## Stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13 on PHP 8.3+ |
| Frontend | Vue 3 + Inertia 3, TypeScript, Tailwind CSS 4 |
| Auth | Laravel Fortify (registration, email verification, 2FA, passkeys) |
| Database | PostgreSQL 16 |
| Cache, sessions, queues | Redis 7 (Horizon to be added with the first queued jobs) |
| Tests | PHPUnit, Larastan, Pint; vue-tsc and Vite+ checks for the frontend |
| Base | `laravel/vue-starter-kit` (see first commit) |

## Request flow

```text
Browser → Nginx/HTTPS → Laravel (+ Inertia)
   ├─ AssignRequestId (every request gets an X-Request-Id)
   ├─ Authentication / authorisation
   ├─ Domain modules (Players, DemoWallet, Games, GameProviders, Compliance, …)
   ├─ PostgreSQL · Redis · private object storage
   └─ Approved provider sandbox endpoints only
```

## Modules

Business logic lives in `app/Domain/<Module>`; HTTP concerns stay in
`app/Http`. Controllers validate, authorise, call a domain service and return
a response.

| Module | Status |
| --- | --- |
| `Compliance` | Launch gate, launch-control record (Milestone 1) |
| `Identity` | Roles, permissions, account status (Milestone 2) |
| `Players` | Player profiles, registration, status changes (Milestone 2) |
| `Audit` | Append-only audit log (Milestone 2) |
| `DemoWallet` | Milestone 3 — demo-credit ledger, separate from any future cash ledger ([ADR-002](decisions/ADR-002-demo-wallet-separation.md)) |
| `Games`, `GameProviders` | Milestones 4–5 |
| `ResponsiblePlay`, `Administration` | Milestones 3–6 |

## Routing

- `routes/web.php`, `routes/settings.php`: Inertia pages.
- `routes/admin.php`: staff area at `/admin` (auth, verified, `admin.access`,
  confirmed 2FA).
- `routes/api.php`: JSON API mounted at `/api/v1` with the standard envelope
  (see [api-contracts.md](api-contracts.md)).
- `real-money` middleware alias: guards anything that would touch real money.
  No such route exists in this release.
